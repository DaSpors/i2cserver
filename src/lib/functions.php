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
	fwrite(STDOUT,implode("\t",$msg)."\n");
	fflush(STDOUT);
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

function hasBit($val,$bit)
{
	$v = pow(2,$bit);
	return ($val & $v) == $v;
}

function bitField($byte,$bits)
{
	$res = ['raw'=>$byte];
	foreach( $bits as $name=>$bit )
		$res[$name] = hasBit($byte,$bit);
	return $res;
}
