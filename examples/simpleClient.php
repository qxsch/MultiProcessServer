<?php

require_once(dirname(__DIR__).'/src/SocketException.php');
require_once(dirname(__DIR__).'/src/SimpleSocketException.php');
require_once(dirname(__DIR__).'/src/SimpleSocket.php');
require_once(dirname(__DIR__).'/src/TCPClient.php');


echo "Sending: hi\n";

$client = new \QXS\MultiProcessServer\TCPClient(12345);  // connect to 127.0.0.1 on port 12345
$client->send("hi");

echo "Receiving: ".$client->receive() ."\n";
