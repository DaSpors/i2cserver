<?php

class RequestHandler
{
	protected static $TOOL_BINARY = false;
	
	var $request, $result, $bus=false, $dev=false;
	
	public static function Process($request,&$result)
	{
		$handler = new RequestHandler($request);
		$ok = $handler->run();
		$result = $handler->result;
		return $ok;
	}
	
	private function __construct($request,$device_mode = false)
	{
		if( self::$TOOL_BINARY === false )
		{
			$arch = trim(shell_exec("uname -m"));
			self::$TOOL_BINARY = realpath(__DIR__."/../bin/i2ctool_$arch");
			if( !self::$TOOL_BINARY )
				throw new Exception("'i2ctool_$arch' not found, please compile first");
		}
		
		$this->request = $request;
		if( $device_mode )
		{
			$this->checkarg('bus',$this->bus);
			$this->checkarg('dev',$this->dev);
		}
	}
	
	protected function checkarg($name,&$target)
	{
		if( $name == '*' )
		{
			$target = isset($this->request['args'])
				?$this->request['args']
				:$this->request['data'];
			return true;
		}
		
		foreach( ['bus','dev'] as $a )
		{
			if( $name != $a || $this->$a === false )
				continue;
			$target = $this->$a;
			return true;
		}
		if( isset($this->request[$name]) && $this->request[$name]!=='' )
		{
			$target = $this->request[$name];
			return true;
		}
		if( isset($this->request['args']) && count($this->request['args'])>0 )
		{
			$target = array_shift($this->request['args']);
			return true;
		}
		$this->result = "Missing argument '$name' ".json_encode($this->request);
		return false;
	}
	
	protected function err($message)
	{
		$this->result = $message;
		return false;
	}
	
	private function run()
	{
		if( !$this->checkarg('cmd',$cmd) ) return false;
		if( !method_exists($this,$cmd) || method_exists(get_parent_class($this),$cmd) )
		{
			if( !$this->checkarg('class',$class) ) 
				return $this->err("Unknown command '$cmd'");
			
			if( file_exists(__DIR__."/../dev/{$class}.class.php") )
				require_once(__DIR__."/../dev/{$class}.class.php");
			if( !class_exists($class) )
				return $this->err("Unknown class '$class'");
			
			$handler = new $class($this->request,true);
			$ok = $handler->run();
			$this->result = $handler->result;
			return $ok;
		}
		return $this->$cmd();
	}
	
	function busses()
	{
		$this->result = [];
		foreach( glob("/sys/bus/i2c/devices/i2c-*/name") as $file )
		{
			$bus = [
				'num'  => intval(substr(dirname($file),25)),
				'name' => trim(file_get_contents($file)),
			];
			$this->result[] = $bus;
		}					
		return true;
	}
	
	function devices()
	{
		if( !$this->checkarg('bus',$bus) ) return false;
		$bus = intarg($bus);
		$lines = shell_exec("i2cdetect -y $bus");
		if( preg_match_all('/[\t ]{1}([a-z0-9]{2})/i',$lines,$matches) )
			$this->result = array_map('hexdec',$matches[1]);
		else
			$this->result = [];
		return true;
	}
	
	function read()
	{
		if( !$this->checkarg('bus',$bus) ) return false;
		if( !$this->checkarg('dev',$dev) ) return false;
		if( !$this->checkarg('reg',$reg) ) return false;
		$this->checkarg('len',$len);
		list($bus,$dev,$reg,$len) = intarg([$bus,$dev,$reg,$len]);
		if( !$len ) $len = 1;
		
		$this->result = [];
		foreach( explode(" ",trim(shell_exec(self::$TOOL_BINARY." $bus $dev 1 $reg $len"))) as $b )
			$this->result[] = hexdec($b);
		return true;
	}

	function write()
	{
		if( !$this->checkarg('bus',$bus) ) return false;
		if( !$this->checkarg('dev',$dev) ) return false;
		if( !$this->checkarg('reg',$reg) ) return false;
		if( !$this->checkarg('*',$data) ) return false;
		list($bus,$dev,$reg) = intarg([$bus,$dev,$reg]);
		$data = intarg($data);
		if( count($data) == 0 )
		{
			$this->result = "Missing data";
			return false;
		}
		shell_exec(self::$TOOL_BINARY." $bus $dev 2 $reg ".implode(" ",$data));
		$this->result = [];
		return true;
	}
	
	function touch()
	{
		if( !$this->checkarg('bus',$bus) ) return false;
		if( !$this->checkarg('dev',$dev) ) return false;
		if( !$this->checkarg('reg',$reg) ) return false;
		list($bus,$dev,$reg) = intarg([$bus,$dev,$reg]);
		shell_exec(self::$TOOL_BINARY." $bus $dev 2 $reg");
		$this->result = [];
		return true;
	}
}