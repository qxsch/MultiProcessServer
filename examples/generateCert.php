<?php

function generatePEMFile($pem_passphrase, $pemfile, $dn, $enforce=false) {
	if(!is_array($dn)) {
		$dn = array(
			'countryName' => 'EU',
			'stateOrProvinceName' => 'Europe',
			'localityName' => 'Europe',
			'organizationName' => 'QXSCH/MultiProcessServer',
			'organizationalUnitName' => 'TLS SERVER',
			'commonName' => 'QXSCH',
			'emailAddress' => 'mail@me.net'
		);
	}

	$enforce = (bool) $enforce;

	if(!file_exists($pemfile) || $enforce) {
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
}

$server_pem_passphrase = 'test';
$server_pemfile = __DIR__.'/server.pem';
$server_dn = array(
	'countryName' => 'EU',
	'stateOrProvinceName' => 'Europe',
	'localityName' => 'Europe',
	'organizationName' => 'QXSCH/MultiProcessServer',
	'organizationalUnitName' => 'TLS SERVER',
	'commonName' => 'QXSCH SERVER',
	'emailAddress' => 'mail@me.net'
);
generatePEMFile($server_pem_passphrase, $server_pemfile, $server_dn);

$client_pem_passphrase = 'test';
$client_pemfile = __DIR__.'/client.pem';
$client_dn = array(
	'countryName' => 'EU',
	'stateOrProvinceName' => 'Europe',
	'localityName' => 'Europe',
	'organizationName' => 'QXSCH/MultiProcessServer',
	'organizationalUnitName' => 'TLS CLIENT',
	'commonName' => 'QXSCH CLIENT',
	'emailAddress' => 'mail@me.net'
);
generatePEMFile($client_pem_passphrase, $client_pemfile, $client_dn);


