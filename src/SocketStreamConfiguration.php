<?php
/**
 * Socket Stream Configuration
 */


namespace QXS\MultiProcessServer;

/**
 * Socket Stream Configuration Class
 */
class SocketStreamConfiguration {
	protected $config=array();

	/**
	 * Enable TLS
	 * @param bool $option true = enable TLS, false = disable TLS
	 */
	public function enableTLS($option=true) {
		if($option===true) {
			if(!@is_array($this->config['ssl'])) {
				$this->config['ssl']=array(
					'disable_compression' => true,
					'capture_peer_cert' => true,
					'capture_peer_cert_chain' => true,
				);
			}
		}
		else {
			if(isset($this->config['ssl'])) {
				unset($this->config['ssl']);
			}
		}
		return $this;
	}

	/**
	 * Disable TLS
	 * @param bool $option false = disable TLS,  true = enable TLS
	 */
	public function disableTLS($option=false) {
		$this->enableTLS(false);
		return $this;
	}

	/**
	 * Verify the Peer
	 * @param bool $option true = verify the peer's certificate
	 */
	public function verifyPeer($option) {
		$this->config['ssl']['verify_peer']=(bool)$option;
		return $this;
	}

	/**
	 * Should the CN of the peers certificate be checked?
	 * @param bool $option true = check the certificate's CN
	 */
	public function setCNMatchCheck($option) {
		if($option) {
			$this->config['ssl']['CN_match']='localhost';
		}
		else {
			unset($this->config['ssl']['CN_match']);
		}
		return $this;
	}

	/**
	 * Set the path to the ca file
	 * @param string $cafile the path to the cafile
	 */
	public function setCaFile($cafile) {
		$this->config['ssl']['cafile']=(string)$cafile;
		$this->config['ssl']['verify_peer']=true;
		return $this;
	}

	/**
	 * Set the path to the client/server cert file
	 * @param string $pemfile the path to the client/server cert file
	 * @param string $pempempassphrase the passphrase for the cert file
	 */
	public function setCert($pemfile, $pempassphrase='') {
		$this->config['ssl']['local_cert']=(string)$pemfile;
		if($pempassphrase!='') {
			$this->config['ssl']['passphrase']=(string)$pempassphrase;
		}
	}

	/**
	 * Should self signed certificates be allowed?
	 * @param bool $option true = allow self signed certificates
	 */
	public function allowSelfSigned($option) {
		 $this->config['ssl']['allow_self_signed']=(bool)$option;
	}

	/**
	 * Set the list of allowed ciphers
	 * @param string $cipherList  the list of allowed ciphers
	 */
	public function setCiphers($cipherList) {
		$this->config['ssl']['ciphers']=(string)$cipherList;
		return $this;
	}

	/**
	 * Checks the configuration an returns all errors
	 * @param bool $throwException  shall the method return an array or throw an exception in case of errors 
	 * @throws  SocketStreamConfigurationException in case, it should throw an exception
	 * @return array  with the the error messages
	 */
	public function checkConfiguration($throwException=false) {
		$errors=array();
		if(@$this->config['ssl']['allow_self_signed']===true && @$this->config['ssl']['verify_peer']!==true) {
			$errors[]='You cannot allow self signed certificates without enabling peer verification.';
		}
		if(@$this->config['ssl']['capath']!='' && @$this->config['ssl']['verify_peer']!==true) {
			$errors[]='You cannot set a ca file without enabling peer verification.';
		}
		return $errors;
	}

	/**
	 * Gets the configuration for the context
	 * @return array  with the the error messages
	 * @see http://php.net/manual/de/context.php
	 */
	public function getContextConfiguration() {
		return $this->config;
	}

	/**
	 * Gets the configured protocol
	 * @return string   either tls or tcp
	 */
	public function getProtocol() {
		if(@is_array($this->config['ssl'])) {
			return 'tls';
		}
		return 'tcp';
	}
}

