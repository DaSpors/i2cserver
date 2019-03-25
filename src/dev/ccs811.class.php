<?php

class CCS811 extends RequestHandler
{
	const REG_STATUS    = 0x00;
	const REG_MEAS_MODE = 0x01;
	const REG_ALG_DATA  = 0x02;
	const REG_NTC       = 0x06;
	const REG_ERROR     = 0xE8;
	const REG_APP_START = 0xF4;
	const REG_RESET     = 0xFF;
	
	const MEAS_MODE_IDLE  = 0b00000000;
	const MEAS_MODE_1SEC  = 0b00010000;
	const MEAS_MODE_10SEC = 0b00100000;
	const MEAS_MODE_60SEC = 0b00110000;
	const MEAS_MODE_250MS = 0b01000000;
	
	const ECO2_MIN = 400;
	const ECO2_MAX = 8192;
	const TVOC_MIN = 0;
	const TVOC_MAX = 1187;
	
	const R_REF      = 100000;
	const R_NTC      = 10000;
	const R_NTC_TEMP = 25;
	const B          = 3380;
	
	private function calcTemp()
	{
#		#define CCS811_R_REF        100000      // resistance of the reference resistor
#		#define CCS811_R_NTC        10000       // resistance of NTC at a reference temperature
#		#define CCS811_R_NTC_TEMP   25          // reference temperature for NTC
#		#define CCS811_BCONSTANT    3380        // B constant
#
#		// get NTC resistance
#		uint32_t r_ntc = ccs811_get_ntc_resistance (sensor, CCS811_R_REF);
#
#		// calculation of temperature from application note ams AN000372
#		double ntc_temp;
#		ntc_temp  = log((double)r_ntc / self::R_NTC);       // 1
#		ntc_temp /= self::B;                                // 2
#		ntc_temp += 1.0 / (self::R_NTC_TEMP + 273.15);      // 3
#		ntc_temp  = 1.0 / ntc_temp;                         // 4
#		ntc_temp -= 273.15;                                 // 5
	}
	
	function validate()
	{
		return parent::validate() && ($this->dev == 0x5A || $this->dev == 0x5B);
	}
	
	function getstatus()
	{
		$ok = $this->handle('read',CCS811::REG_STATUS);
		$this->result = bitField($this->result[0],['error'=>0,'datardy'=>3,'appok'=>4,'fw_mode'=>7,'appmode'=>7]);
		$this->result['bootmode'] = !$this->result['appmode'];
		return $ok;
	}
	
	function getmode()
	{
		$ok = $this->handle('read',CCS811::REG_MEAS_MODE);
		switch( $this->result[0] & 0b01110000 )
		{
			case self::MEAS_MODE_IDLE: $mode = 'idle'; break;
			case self::MEAS_MODE_1SEC: $mode = '1sec'; break;
			case self::MEAS_MODE_10SEC: $mode = '10sec'; break;
			case self::MEAS_MODE_60SEC: $mode = '1min'; break;
			case self::MEAS_MODE_250MS: $mode = '250ms'; break;
			default: $mode = 'undef'; break;
		}
		$this->result = bitField($this->result[0],['tresh'=>2,'interrupt'=>3]);
		$this->result['mode'] = $mode;
		return $ok;
	}
	
	function start()
	{
		return $this->handle('touch',CCS811::REG_APP_START);
	}
	
	function reboot()
	{
		return $this->handle('write',CCS811::REG_RESET,[0x11, 0xE5, 0x72, 0x8A]);
	}

	function setmode()
	{
		if( !$this->checkarg('mode',$mode) ) return false;
		switch( strtolower($mode) )
		{
			case 'idle': $mode = self::MEAS_MODE_IDLE; break;
			case '250ms': $mode = self::MEAS_MODE_250MS; break;
			case '1sec': $mode = self::MEAS_MODE_1SEC; break;
			case '10sec': $mode = self::MEAS_MODE_10SEC; break;
			case '1min': $mode = self::MEAS_MODE_60SEC; break;
			default: return $this->err("Invalid mode '$mode'");
		}
		return $this->handle('write',CCS811::REG_MEAS_MODE,[$mode]);
	}
	
	function getvalues()
	{
		$ok = $this->handle('read',CCS811::REG_ALG_DATA,6);
		$raw = $this->result;
		$eco2 = ($raw[0]<<8) + $raw[1];
		$tvoc = ($raw[2]<<8) + $raw[3];
		
		if( !$ok 
			|| $eco2 < self::ECO2_MIN || $eco2 > self::ECO2_MAX
			|| $tvoc < self::TVOC_MIN || $tvoc > self::TVOC_MAX )
		{
			$this->result = [];
			return false;
		}
		$this->result = compact('raw','eco2','tvoc');
		return $ok;
	}
}
