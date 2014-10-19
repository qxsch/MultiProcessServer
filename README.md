MultiProcessServer
==================

![Project Status](http://stillmaintained.com/qxsch/MultiProcessServer.png)

[![Latest Stable Version](https://poser.pugx.org/qxsch/multi-process-server/v/stable.png)](https://packagist.org/packages/qxsch/worker-pool) [![Total Downloads](https://poser.pugx.org/qxsch/worker-pool/downloads.png)](https://packagist.org/packages/qxsch/worker-pool) [![License](https://poser.pugx.org/qxsch/worker-pool/license.png)](https://packagist.org/packages/qxsch/worker-pool)

**A multithreaded server for PHP**

The TCPServer class provides a very simple interface to communicate with a client. You can control how many processes should be allowed to run concurrently. The TCPServer can be fully observed.

The TCPClient class provides a very simple interface to communicate with a server.

You can send any data between the client and the server that can be [serialized][serialize].


### A simple example

Server Code:
```php
<?php

$server = new \QXS\MultiProcessServer\TCPServer(12345);  // setup the server for 127.0.0.1 on port 12345
// UNCOMMENT THE NEXT LINE TO SEE WHAT THE SERVER IS DOING
//$server->attach(new \QXS\MultiProcessServer\Observers\EchoObserver());
$server->create(new \QXS\MultiProcessServer\ClosureServerWorker(
    /**
     * @param \QXS\MultiProcessServer\SimpleSocket $serverSocket the socket to communicate with the client
     */
    function(\QXS\MultiProcessServer\SimpleSocket $serverSocket) {
        // receive data and send it back
        $data=$serverSocket->receive();
        echo "$data\n";
        $serverSocket->send($data);
    }
));
```

Client Code:
```php
<?php

$client = new \QXS\MultiProcessServer\TCPClient(12345);  // connect to 127.0.0.1 on port 12345
$client->send("hi");

echo $client->receive() ."\n";
```




  [serialize]: http://php.net/serialize
