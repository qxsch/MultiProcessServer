<?php

require_once(dirname(__DIR__).'/autoload.php');


$server = new \QXS\MultiProcessServer\TCPServer(12345);  // setup the server for 127.0.0.1 on port 12345
$server->attach(new \QXS\MultiProcessServer\Observers\EchoObserver());  // say what the server is doing
$server->create(new \QXS\MultiProcessServer\ClosureServerWorker(
    /**
     * @param \QXS\MultiProcessServer\SimpleSocketStream $serverSocket the socket to communicate with the client
     */
    function(\QXS\MultiProcessServer\SimpleSocketStream $serverSocket) {
        // receive data and send it back
        if(!$serverSocket->hasData(2)) {
		$serverSocket->send('timeout reached');
		return null;
	}
        $data=(string)$serverSocket->receive();
        echo "Received: $data\n";
	$data=strrev($data);
        echo "Sending $data\n";
        $serverSocket->send($data);
    }
));
