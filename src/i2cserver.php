<?php

require_once(__DIR__."/lib/functions.php");
require_once(__DIR__."/lib/server.class.php");
require_once(__DIR__."/lib/connection.class.php");
require_once(__DIR__."/lib/requesthandler.class.php");

$arguments = $defaults = 
[
	'scheme' => 'tcp',
	'ip'     => '127.0.0.1',
	'port'   => '8088',
	'daemon' => 'no'
];
array_shift($argv);
foreach( $argv as $a )
{
	$nv = explode("=",$a,2);
	$n = trim(trim(array_shift($nv),"-/"));
	$v = array_shift($v);
	$arguments[$n] = $v?$v:(isset($defaults[$n])?$defaults[$n]:null);
}

// todo: validate argument values

$server = new Server($arguments);
while( true )
	$server->listen();
