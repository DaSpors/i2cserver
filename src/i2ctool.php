<?php

require_once(__DIR__."/lib/functions.php");
require_once(__DIR__."/lib/requesthandler.class.php");

array_shift($argv);
if( count($argv)<1 )
	dieSyntax();

$syntax = 
[
	'busses' => [],
	'devices' => ['bus'],
	'read' => ['bus','dev','reg','len'],
	'write' => ['bus','dev','reg','len'],
];
$format = 'dec';
$request = [];
$request['cmd'] = array_shift($argv);
switch( $request['cmd'] )
{
	case 'bin': case 'hex':
		$format = $request['cmd'];
		$request['cmd'] = 'read';
		break;
}

if( file_exists(__DIR__."/dev/{$request['cmd']}.class.php") )
{
	$request['class'] = $request['cmd'];
	$request['cmd'] = array_shift($argv);
}
$request['args'] = $argv;

function formatInt(&$value)
{
	global $format;
	if( !is_integer($value) )
		return;
	if( $format == 'hex' )
	{
		$value = dechex($value);
		if( strlen($value) % 2 != 0 )
			$value = "0$value";
		$value = "0x$value";
	}
	elseif( $format == 'bin' )
	{
		$value = decbin($value);
		while( strlen($value) % 8 != 0 )
			$value = "0$value";
		$value = "0b$value";
	}
}

$ok = RequestHandler::Process($request,$res);
if( !is_string($res) )
{
	if( $format != 'dec' )
	{
		array_walk_recursive($res,'formatInt');
		$res = implode(" ",$res);
	}
	else
		$res = json_encode($res);
}
echo("$res\n");
exit($ok?0:1);