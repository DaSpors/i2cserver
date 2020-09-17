<?php

function writeln()
{
	$msg = [date("c")];
	foreach( func_get_args() as $a )
	{
		if( is_array($a) || is_object($a) )
			$msg[] = json_encode($a);
		else
			$msg[] = $a;
	}
	if( defined('stdout') )
	{
		fwrite(STDOUT,implode("\t",$msg)."\n");
		fflush(STDOUT);
	}
	else error_log(implode("\t",$msg));
}

function dieError($message)
{
	writeln($message);
	die();
}

function fork()
{
	$pid = pcntl_fork();
	if( $pid < 0 )
		dieError('Cannot fork subprocess');
	if( $pid > 0 )
		return true;
	return false;
}

function intarg($v)
{
	if( is_array($v) )
		return array_map('intarg',$v);

	if( is_numeric($v) )
		return intval($v);
	if( stripos($v,"b") !== false )
		return base_convert($v,2,10);
	return hexdec($v);
}

function boolarg($v)
{
	if( is_array($v) )
		return array_map('boolarg',$v);

	$v = strtolower($v);
	if( is_numeric($v) )
		return intval($v) == true;
	return $v == 'on' || $v =='true' || $v == 'yes' || $v == 'y';
}

function enumarg($v,$values)
{
	if( is_array($v) )
	{
		$res = [];
		foreach( $v as $s )
			$res[] = enumarg($s,$values);
		return $res;
	}
	$values = array_map('strtolower',$values);
	$i = array_search(strtolower($v),$values);
	if( $i !== false )
		return $values[$i];
	return false;
}

function hasBit($val,$bit)
{
	$v = pow(2,$bit);
	return ($val & $v) == $v;
}

function bitField($byte,$bits,$noraw=false)
{
	$res = $noraw?[]:['raw'=>$byte,'hex'=>"0x".dechex($byte)];
	foreach( $bits as $name=>$bit )
		$res[$name] = hasBit($byte,$bit);
	return $res;
}
