<?php

require_once(dirname(__DIR__).'/SocketException.php');
require_once(dirname(__DIR__).'/SimpleSocketException.php');
require_once(dirname(__DIR__).'/SimpleSocket.php');
require_once(dirname(__DIR__).'/TCPClient.php');


echo "Sending: hi\n";

$client = new \QXS\MultiProcessServer\TCPClient(12345);  // connect to 127.0.0.1 on port 12345
$client->send("hi");

echo "Receiving: ".$client->receive() ."\n";
