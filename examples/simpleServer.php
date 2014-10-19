<?php

require_once(dirname(__DIR__).'/src/Observers/ObserverInterface.php');
require_once(dirname(__DIR__).'/src/Observers/EchoObserver.php');
require_once(dirname(__DIR__).'/src/Subjects/SubjectInterface.php');
require_once(dirname(__DIR__).'/src/SocketException.php');
require_once(dirname(__DIR__).'/src/SimpleSocketException.php');
require_once(dirname(__DIR__).'/src/SimpleSocket.php');
require_once(dirname(__DIR__).'/src/ServerWorkerInterface.php');
require_once(dirname(__DIR__).'/src/ClosureServerWorker.php');
require_once(dirname(__DIR__).'/src/TCPServer.php');


$server = new \QXS\MultiProcessServer\TCPServer(12345);  // setup the server for 127.0.0.1 on port 12345
$server->attach(new \QXS\MultiProcessServer\Observers\EchoObserver());  // say what the server is doing
$server->create(new \QXS\MultiProcessServer\ClosureServerWorker(
    /**
     * @param \QXS\MultiProcessServer\SimpleSocket $serverSocket the socket to communicate with the client
     */
    function(\QXS\MultiProcessServer\SimpleSocket $serverSocket) {
        // receive data and send it back
        $data=(string)$serverSocket->receive();
        echo "Received: $data\n";
	$data=strrev($data);
        echo "Sending $data\n";
        $serverSocket->send($data);
    }
));
