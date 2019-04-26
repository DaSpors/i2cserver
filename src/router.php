<?php

require_once(__DIR__."/lib/functions.php");
require_once(__DIR__."/lib/requesthandler.class.php");

$data = parse_url($_SERVER['REQUEST_URI']);
if( !isset($data['path']) || !$data['path'] )
	die("Missing command");

parse_str($data['query'],$arguments);
$check = explode("/",trim($data['path'],"/"));
$arguments['cmd'] = array_shift($check);
if( count($check)>0 )
{
	$arguments['class'] = $arguments['cmd'];
	$arguments['cmd'] = array_shift($check);
}
if( count($check)>0 )
	die("Invalid syntax");

//var_dump($arguments);

$ok = RequestHandler::Process($arguments,$res);
if( !$ok ) $res = ['error'=>$res];
header("Content-Type: application/json");
die(json_encode($res));
