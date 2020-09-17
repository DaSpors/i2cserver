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
	const REG_BASELINE  = 0x11;
	
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
		$status = $this->devRead(CCS811::REG_STATUS);
		$this->result = ['error' => 'Error reading'];
		if( count($status)==0 )
			return false;
		$this->result = bitField($status,['error'=>0,'datardy'=>3,'appok'=>4,'fw_mode'=>7,'appmode'=>7]);
		$this->result['bootmode'] = !$this->result['appmode'];
		return true;
	}
	
	function getmode()
	{
		$raw = $this->devRead(CCS811::REG_MEAS_MODE);
		$this->result = ['error'=>'Error reading'];
		if( count($raw)==0 )
			return false;
		switch( $raw & 0b01110000 )
		{
			case self::MEAS_MODE_IDLE: $mode = 'idle'; break;
			case self::MEAS_MODE_1SEC: $mode = '1sec'; break;
			case self::MEAS_MODE_10SEC: $mode = '10sec'; break;
			case self::MEAS_MODE_60SEC: $mode = '1min'; break;
			case self::MEAS_MODE_250MS: $mode = '250ms'; break;
			default: $mode = 'undef'; break;
		}
		$this->result = bitField($raw,['tresh'=>2,'interrupt'=>3]);
		$this->result['mode'] = $mode;
		return true;
	}
	
	function start()
	{
		$this->devWrite(CCS811::REG_APP_START,[]);
		return true;
	}
	
	function reboot()
	{
		$this->devWrite(CCS811::REG_RESET,[0x11, 0xE5, 0x72, 0x8A]);
		return true;
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
		$this->devWrite(CCS811::REG_MEAS_MODE,[$mode]);
		return true;
	}
	
	function getvalues()
	{
		if( !$this->getmode() )
			return false;
		if( $this->result['mode'] == self::MEAS_MODE_IDLE )
		{
			$this->result = ["error" => "Sensor in idle mode"];
			return false;
		}
		
		if( !$this->getstatus() )
			return false;
		if( !$this->result['datardy'] )
		{
			$this->result = [];
			return false;
		}
		
		$this->result = ['error'=>'Error reading'];
		$raw = $this->devRead(CCS811::REG_ALG_DATA,8);
		if( count($raw)==0 )
			return false;
		
		$eco2 = ($raw[0]<<8) + $raw[1];
		$tvoc = ($raw[2]<<8) + $raw[3];
		$status = bitField($raw[4],['error'=>0,'datardy'=>3,'appok'=>4,'fw_mode'=>7,'appmode'=>7],true);
		$status['bootmode'] = !$status['appmode'];
		$error = bitField($raw[5],['WRITE_REG_INVALID'=>0,'READ_REG_INVALID'=>1,'MEASMODE_INVALID'=>2,'MAX_RESISTANCE'=>3,'HEATER_FAULT'=>4,'HEATER_SUPPLY'=>5],true);
		
		$raw_av  = ($raw[6]<<8) + $raw[7];
		$current = $raw_av >> 10;
		$voltage = ($raw_av & 0b1111111111) / 1023 * 1.65; // see datasheet
		
		$baseline = $this->devRead(self::REG_BASELINE);
		
		$this->result = compact('raw','eco2','tvoc','status','error','current','voltage','baseline');
		return true;
	}

	function getError()
	{
		$err = $this->devRead(CCS811::REG_ERROR);
		$this->result = ['error'=>'Error reading'];
		if( count($err) == 0 )
			return false;
		$this->result = bitField($err,['WRITE_REG_INVALID'=>0,'READ_REG_INVALID'=>1,'MEASMODE_INVALID'=>2,'MAX_RESISTANCE'=>3,'HEATER_FAULT'=>4,'HEATER_SUPPLY'=>5]);
		return true;
	}
}
