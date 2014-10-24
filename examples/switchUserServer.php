<?php

require_once(dirname(__DIR__).'/autoload.php');


if(posix_getuid()!==0) {
	die("Please run this as root...\n");
}



$server = new \QXS\MultiProcessServer\TCPServer(12345);  // setup the server for 127.0.0.1 on port 12345
$server->runAsUser("nobody");
$server->attach(new \QXS\MultiProcessServer\Observers\EchoObserver());  // say what the server is doing
$server->create(new \QXS\MultiProcessServer\ClosureServerWorker(
    /**
     * @param \QXS\MultiProcessServer\SimpleSocket $serverSocket the socket to communicate with the client
     */
    function(\QXS\MultiProcessServer\SimpleSocket $serverSocket) {
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