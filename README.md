MultiProcessServer
==================


**A multithreaded server for PHP**

The TCPServer class provides a very simple interface to communicate with a client.

The TCPClient class provides a very simple interface to communicate with a server.


### A simple example

Server Code:
```php
<?php

$server = new \QXS\MultiProcessServer\TCPServer(12345);  // setup the server for 127.0.0.1 on port 12345
$server->create(new \QXS\MultiProcessServer\ClosureServerWorker(function(\QXS\MultiProcessServer\SimpleSocket $serverSocket) {
    // receive data and send it back
    $data=$serverSocket->receive();
    echo "$data\n";
    $serverSocket->send($data);
}));
```

Client Code:
```php
<?php

$client = new \QXS\MultiProcessServer\TCPClient(12345);  // connect to 127.0.0.1 on port 12345
$client->send("hi");

echo $client->receive() ."\n";
```
