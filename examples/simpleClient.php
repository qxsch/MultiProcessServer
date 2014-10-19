<?php

require_once(dirname(__DIR__).'/autoload.php');

echo "Sending: hi\n";

$client = new \QXS\MultiProcessServer\TCPClient(12345);  // connect to 127.0.0.1 on port 12345
$client->send("hi");

echo "Receiving: ".$client->receive() ."\n";
