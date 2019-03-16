<?php

class Connection
{
	var $disconnected = false;
	
	function __construct($stream)
	{
		$this->stream = $stream;
		writeln("Client connected");
	}
	
	function run()
	{
		while( !$this->disconnected )
		{
			$raw = @stream_socket_recvfrom($this->stream,2048);
			$req = @json_decode($raw,true);
			if( !$req )
				$this->errResponse("Invalid request");
			$ok = RequestHandler::Process($req,$res);
			if( $ok )
				$this->okResponse($res);
			else
				$this->errResponse($res);
		}
		writeln("Client disconnected");
	}
	
	private function send($data)
	{
		$resp = json_encode($data);
		$l = @stream_socket_sendto($this->stream,$resp);
		if( $l<1 )
			$this->disconnected = true;
	}
	
	private function okResponse($result)
	{
		$this->send(['status'=>'ok','result'=>$result]);
	}
	
	private function errResponse($message)
	{
		$this->send(['status'=>'err','message'=>"$message"]);
	}
}