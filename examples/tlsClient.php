<?php

require_once(dirname(__DIR__).'/autoload.php');
require_once(__DIR__.'/generateCert.php');

$streamConfig = new \QXS\MultiProcessServer\SocketStreamConfiguration();
$streamConfig
	->enableTLS(true)
	->verifyPeer(false)
	//->setCNMatchCheck(false)
	//->setCaFile(__DIR__.'/ca-certificates.crt')
	//->setCiphers('ALL:!aNULL:!ADH:!eNULL:!LOW:!EXP:RC4+RSA:+HIGH:-MEDIUM')
	//->setCert($pemfile, $pem_passphrase)
	//->allowSelfSigned(true)
;


$client = new \QXS\MultiProcessServer\TCPClient(
	12345, 
	'127.0.0.1',
	$streamConfig
);

$serverCert=$client->parsePeerX509Certificate();
var_dump($serverCert['subject']);

echo "Sending: hi\n";
$client->send("hi");

echo "Receiving: ".$client->receive() ."\n";
