<?php

class IOPiPlus extends RequestHandler
{
	const REG_DIRA = 0x00;
	const REG_DIRB = 0x01;
	const REG_PORTA = 0x12;
	const REG_PORTB = 0x13;

	private function onOffResult($reg1,$reg2,$trueval=true,$falseval=false)
	{
		$raw = $this->devRead($reg1);
		$r2 = $this->devRead($reg2);
		$raw = ($r2<<8) | $raw;
		$pins = [];
		for($i=1,$p=0; $i<=0b1000000000000000; $i=$i<<1,$p++)
			$pins[$p] = (($raw & $i)==$i)?$trueval:$falseval;
		$raw = "0x".dechex($raw);
		$this->result = compact('raw','pins');
		return true;
	}
	
	private function decoderaw($raw,$trueval=true,$falseval=false)
	{
		$pins = [];
		for($i=1,$p=0; $i<=0b10000000; $i=$i<<1,$p++)
			$pins[$p] = (($raw & $i)==$i)?$trueval:$falseval;
		return $pins;
	}
	
	function validate()
	{
		return parent::validate() && $this->dev >= 0x20 && $this->dev <= 0x27;
	}
	
	function getmodes()
	{
		return $this->onOffResult(self::REG_DIRA,self::REG_DIRB,'in','out');
	}

	function getstates()
	{
		return $this->onOffResult(self::REG_PORTA,self::REG_PORTB,'on','off');
	}

	function getpin()
	{
		if( !$this->checkarg('pin',$pin) ) return $this->err("Missing argument 'pin'");
		$dir  = ($pin < 8)?self::REG_DIRA:self::REG_DIRB;
		$port = ($pin < 8)?self::REG_PORTA:self::REG_PORTB;
		$dir  = $this->devRead($dir);
		$port = $this->devRead($port);
		$pin  = ($pin < 8)?$pin:($pin-8);
		$this->result = [
			'mode' => hasBit($dir,$pin)?'in':'out',
			'value' => hasBit($port,$pin)?'on':'off',
		];
		return true;
	}
	
	function setmode()
	{
		if( !$this->checkarg('pin',$pin) ) return $this->err("Missing argument 'pin'");
		if( !$this->checkarg('mode',$mode) ) return $this->err("Missing argument 'mode'");
		$mode = enumarg($mode,['in','out']);
		if( !$mode ) return $this->err("Invalid value for 'mode'. Allowed: in|out");
		$reg = ($pin < 8)?self::REG_DIRA:self::REG_DIRB;
		$val = $this->devRead($reg);
		if( $mode == 'in' )
			$val = $val | pow(2,$pin);
		else
			$val = $val & ~pow(2,$pin);
		$this->devWrite($reg,$val);
		return $this->getpin();
	}
	
	function setstate()
	{
		if( !$this->checkarg('pin',$pin) ) return $this->err("Missing argument 'pin'");
		if( !$this->checkarg('state',$state) ) return $this->err("Missing argument 'state'");
		$state = enumarg($state,['on','off']);
		if( !$state ) return $this->err("Invalid value for 'state'. Allowed: on|off");
		$reg = ($pin < 8)?self::REG_PORTA:self::REG_PORTB;
		$val = $this->devRead($reg);
		if( $state == 'on' )
                        $val = $val | pow(2,$pin);
                else
                        $val = $val & ~pow(2,$pin);
                $this->devWrite($reg,$val);
		return $this->getpin();
	}
}
