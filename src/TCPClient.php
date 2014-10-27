<?php
/**
 * Socket Server
 */


namespace QXS\MultiProcessServer;


class TCPClient extends SimpleSocketStream {
	protected $socketConfig;

	public function __construct($port, $address = '127.0.0.1', SocketStreamConfiguration $socketConfig=null) {
		$address=(string) $address;
		$port=(int)$port;

		if($socketConfig===null) {
			$this->socketConfig=new SocketStreamConfiguration();
		}
		else {
			$this->socketConfig=$socketConfig;
		}
		// check the configuration and throw an exception in case of an error
		$this->socketConfig->checkConfiguration(true);

		$configuration=$this->socketConfig->getContextConfiguration();
		if(isset($configuration['ssl']['CN_match'])) {
			$configuration['ssl']['CN_match']=$address;
		}
		$context=stream_context_create($configuration);

		$this->socket=stream_socket_client($this->socketConfig->getProtocol().'://'.$address . ':' . $port, $errno, $errstr, 60, STREAM_CLIENT_CONNECT, $context);
		if($this->socket===false) {
			throw new SocketException('Failed to create the socket. ' . $errstr, $errno);
		}

	}

}

