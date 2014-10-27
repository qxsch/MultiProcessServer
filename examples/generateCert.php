<?php

$pem_passphrase = 'test';
$pemfile = __DIR__.'/server.pem';


if(!file_exists($pemfile)) {
	// Certificate data:
	$dn = array(
		'countryName' => 'EU',
		'stateOrProvinceName' => 'Europe',
		'localityName' => 'Europe',
		'organizationName' => 'QXSCH/MultiProcessServer',
		'organizationalUnitName' => 'TLS SERVER',
		'commonName' => 'QXSCH',
		'emailAddress' => 'mail@me.net'
	);

	// Generate certificate
	$privkey = openssl_pkey_new();
	$cert    = openssl_csr_new($dn, $privkey);
	$cert    = openssl_csr_sign($cert, null, $privkey, 365);

	// Generate PEM file
	$pem = array();
	openssl_x509_export($cert, $pem[0], false);
	openssl_pkey_export($privkey, $pem[1], $pem_passphrase);
	$pem = implode($pem);

	// Save PEM file
	file_put_contents($pemfile, $pem);
}

$context = stream_context_create();

// local_cert must be in PEM format
stream_context_set_option($context, 'ssl', 'local_cert', $pemfile);
// Pass Phrase (password) of private key
stream_context_set_option($context, 'ssl', 'passphrase', $pem_passphrase);
stream_context_set_option($context, 'ssl', 'allow_self_signed', true);
stream_context_set_option($context, 'ssl', 'verify_peer', false);

// Create the server socket
$server = stream_socket_server('tls://0.0.0.0:9001', $errno, $errstr, STREAM_SERVER_BIND|STREAM_SERVER_LISTEN, $context);

var_dump(get_resource_type($server));
