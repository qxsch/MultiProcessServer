<?php
/**
 * Socket Server
 */


namespace QXS\MultiProcessServer;


class TCPClient extends SimpleSocketStream {

	public function __construct($port, $address = '127.0.0.1') {
		$address=(string) $address;
		$port=(int)$port;

		$this->socket=@socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		if($this->socket===false) {
			throw new SocketException('Failed to create the socket. ' . socket_strerror(socket_last_error()));
		}

		if(@socket_connect($this->socket, $address, $port)===false) {
			throw new SocketException('Failed to bind the socket on ' . $address . ':' . $port . '. ' . socket_strerror(socket_last_error($this->socket)));
		}
	}

}

