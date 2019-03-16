<?php

class Server
{
	var $socket;
	
	function __construct($arguments)
	{
		extract($arguments);
		$this->socket = stream_socket_server("$scheme://$ip:$port",$errno,$errstr);
		if( !$this->socket )
			dieError($errstr);
	}
	
	function listen()
	{
		$stream = @stream_socket_accept($this->socket,5);
		if( $stream )
			$this->handleConnection($stream);
	}
	
	function handleConnection($stream)
	{
		if( fork() )
			return;
		$c = new Connection($stream);
		$c->run();
	}
}