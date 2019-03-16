<?php

$socket = stream_socket_client('tcp://127.0.0.1:8088');

$busses  = [ 'cmd'=>'busses' ];
$devices = [ 'cmd'=>'devices', 'bus'=>1 ];
$hwid = [ 'cmd'=>'read', 'bus'=>1, 'dev'=>0x5a, 'reg'=>0x20 ];
$read = [ 'cmd'=>'read', 'bus'=>1, 'dev'=>0x5a, 'reg'=>0x02, 'len'=>6 ];

$read = [ 'class'=>'ccs811', 'cmd'=>'getstatus', 'bus'=>1, 'dev'=>0x5a ];
$read = [ 'class'=>'ccs811', 'cmd'=>'getvalues', 'bus'=>1, 'dev'=>0x5a ];

foreach( [$busses,$devices,$hwid,$read] as $cmd )
{
	echo "\n>>> ".json_encode($cmd)."\n";
	stream_socket_sendto($socket, json_encode($cmd));
	$raw = @stream_socket_recvfrom($socket,2048);
	echo "<<< $raw\n";
}

fclose($socket);