<?php

require_once(dirname(__DIR__).'/Observers/ObserverInterface.php');
require_once(dirname(__DIR__).'/Observers/EchoObserver.php');
require_once(dirname(__DIR__).'/Subjects/SubjectInterface.php');
require_once(dirname(__DIR__).'/SocketException.php');
require_once(dirname(__DIR__).'/SimpleSocketException.php');
require_once(dirname(__DIR__).'/SimpleSocket.php');
require_once(dirname(__DIR__).'/ServerWorkerInterface.php');
require_once(dirname(__DIR__).'/ClosureServerWorker.php');
require_once(dirname(__DIR__).'/TCPServer.php');


$server = new \QXS\MultiProcessServer\TCPServer(12345);  // setup the server for 127.0.0.1 on port 12345
$server->attach(new \QXS\MultiProcessServer\Observers\EchoObserver());  // say what the server is doing
$server->create(new \QXS\MultiProcessServer\ClosureServerWorker(
    /**
     * @param \QXS\MultiProcessServer\SimpleSocket $serverSocket the socket to communicate with the client
     */
    function(\QXS\MultiProcessServer\SimpleSocket $serverSocket) {
        // receive data and send it back
        $data=$serverSocket->receive();
        echo "$data\n";
	sleep(1);
        $serverSocket->send($data);
    }
));
