MultiProcessServer
==================

[![Project Status](http://www.repostatus.org/badges/latest/active.svg)](http://www.repostatus.org/#active)

[![Latest Stable Version](https://poser.pugx.org/qxsch/multi-process-server/v/stable.png)](https://packagist.org/packages/qxsch/multi-process-server) [![Total Downloads](https://poser.pugx.org/qxsch/multi-process-server/downloads.png)](https://packagist.org/packages/qxsch/multi-process-server) [![License](https://poser.pugx.org/qxsch/multi-process-server/license.png)](https://packagist.org/packages/qxsch/multi-process-server)

**A multithreaded server for PHP**

The TCPServer class provides a very simple interface to communicate with a client. You can control how many processes should be allowed to run concurrently. The TCPServer can be fully observed.

The TCPClient class provides a very simple interface to communicate with a server.

You can send any data between the client and the server that can be [serialized][serialize].

TLS Encryption with server and client certificate is supported (mutual authentication).

### A simple example

Server Code:
```php
<?php

$server = new \QXS\MultiProcessServer\TCPServer(12345);  // setup the server for 127.0.0.1 on port 12345
// UNCOMMENT THE NEXT LINE TO ADD IMPERSONATION
//$server->runAsUser("nobody");
$server->attach(new \QXS\MultiProcessServer\Observers\EchoObserver());
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
