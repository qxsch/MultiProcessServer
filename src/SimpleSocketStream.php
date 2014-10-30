<?php
/**
 * A simple object wrapper arround the socket stream functions
 */

namespace QXS\MultiProcessServer;

/**
 * Socket Stream Class for IPC
 */
class SimpleSocketStream {

	/** @var resource the connection socket stream, that is used for IPC */
	protected $socket = NULL;
	/**
	 * This Variable can be used to attack custom information to the socket
	 * @var array of custom annotations
	 */
	public $annotation = array();

	/**
	 * The constructor
	 * @param resource $socket a valid socket resource
	 * @throws \InvalidArgumentException
	 */
	public function __construct($socket) {
		if (!is_resource($socket) && strtolower(@get_resource_type($socket) != 'stream')) {
			throw new \InvalidArgumentException('Stream resource is required!');
		}
		$data=stream_get_meta_data($socket);
		if(strpos($data['stream_type'], 'tcp_socket')===false) {
			throw new \InvalidArgumentException('TCP Socket Stream resource is required!');
		}

		$this->socket = $socket;
	}

	/**
	 * The destructor
	 */
	public function __destruct() {
		@fclose($this->socket);
	}


	/**
	 * Selects active sockets with a timeout
	 * @param SimpleSocketStream[] $readStreams Array of \QXS\MultiProcessServer\SimpleSocketStream Objects, that should be monitored for read activity
	 * @param SimpleSocketStream[] $writeStreams Array of \QXS\MultiProcessServer\SimpleSocketStream Objects, that should be monitored for write activity
	 * @param SimpleSocketStream[] $exceptStreams Array of \QXS\MultiProcessServer\SimpleSocketStream Objects, that should be monitored for except activity
	 * @param int $sec seconds to wait until a timeout is reached
	 * @param int $usec microseconds to wait a timeout is reached
	 * @return array Associative Array of \QXS\MultiProcessServer\SimpleSocketStream Objects, that matched the monitoring, with the following keys 'read', 'write', 'except'
	 */
	public static function select(array $readStreams = array(), array $writeStreams = array(), array $exceptStreams = array(), $sec = 0, $usec = 0) {
		$out = array();
		$out['read'] = array();
		$out['write'] = array();
		$out['except'] = array();

		if(count($readStreams) === 0){
			return $out;
		}

		$readStreamsResources = array();
		$writeStreamsResources = array();
		$exceptStreamsResources = array();
		$readStreams = self::createSocketsIndex($readStreams, $readStreamsResources);
		$writeStreams = self::createSocketsIndex($writeStreams, $writeStreamsResources);
		$exceptStreams = self::createSocketsIndex($exceptStreams, $exceptStreamsResources);

		$socketsSelected = stream_select($readStreamsResources, $writeStreamsResources, $exceptStreamsResources, $sec, $usec);
		if ($socketsSelected === FALSE) {
			return $out;
		}

		foreach ($readStreamsResources as $socketResource) {
			$out['read'][] = $readStreams[intval($socketResource)];
		}
		foreach ($writeStreamsResources as $socketResource) {
			$out['write'][] = $writeStreams[intval($socketResource)];
		}
		foreach ($exceptStreamsResources as $socketResource) {
			$out['except'][] = $exceptStreams[intval($socketResource)];
		}

		return $out;
	}

	/**
	 * @param SimpleSocketStream[] $sockets
	 * @param array $socketsResources
	 * @return SimpleSocketStream[]
	 */
	protected static function createSocketsIndex($sockets, &$socketsResources) {
		$socketsIndex = array();
		foreach ($sockets as $socket) {
			if (!$socket instanceof SimpleSocketStream) {
				continue;
			}
			$resourceId = $socket->getResourceId();
			$socketsIndex[$resourceId] = $socket;
			$socketsResources[$resourceId] = $socket->getSocket();
		}

		return $socketsIndex;
	}

	/**
	 * Get the id of the provider resource
	 * @return int the id of the socket resource
	 */
	public function getResourceId() {
		return intval($this->socket);
	}

	/**
	 * Get the socket resource
	 * @return resource the socket resource
	 */
	public function getSocket() {
		return $this->socket;
	}

	/**
	 * Check if there is any data available
	 * @param int $sec seconds to wait until a timeout is reached
	 * @param int $usec microseconds to wait a timeout is reached
	 * @return bool true, in case there is data, that can be red
	 */
	public function hasData($sec = 0, $usec = 0) {
		$sec = (int)$sec;
		$usec = (int)$usec;
		if ($sec < 0) {
			$sec = 0;
		}
		if ($usec < 0) {
			$usec = 0;
		}

		$read = array($this->socket);
		$write = array();
		$except = array();
		$sockets = stream_select($read, $write, $except, $sec, $usec);

		if ($sockets === FALSE) {
			return FALSE;
		}
		return $sockets > 0;
	}

	/**
	 * Write the data to the socket in a predetermined format
	 * @param mixed $data the data, that should be sent
	 * @throws \QXS\MultiProcessServer\SimpleSocketStreamException in case of an error
	 */
	public function send($data) {
		$serialized = serialize($data);
		$hdr = pack('N', strlen($serialized)); // 4 byte length
		$buffer = $hdr . $serialized;
		unset($serialized);
		unset($hdr);
		$total = strlen($buffer);
		while ($total > 0) {
			$sent = @fwrite($this->socket, $buffer);
			if ($sent === FALSE) {
				throw new SimpleSocketStreamException('Sending failed.');
				break;
			}
			$total -= $sent;
			$buffer = substr($buffer, $sent);
		}
	}

	/**
	 * Read a data packet from the socket in a predetermined format.
	 * @throws \QXS\MultiProcessServer\SimpleSocketStreamException in case of an error
	 * @return mixed the data, that has been received
	 */
	public function receive() {
		// read 4 byte length first
		$hdr = '';
		do {
			$read = fread($this->socket, 4 - strlen($hdr));
			if ($read === FALSE) {
				throw new SimpleSocketStreamException('Reception failed.');
			} elseif ($read === '' || $read === NULL) {
				return NULL;
			}
			$hdr .= $read;
		} while (strlen($hdr) < 4);

		list($len) = array_values(unpack("N", $hdr));

		// read the full buffer
		$buffer = '';
		do {
			$read = fread($this->socket, $len - strlen($buffer));
			if ($read === FALSE || $read == '') {
				return NULL;
			}
			$buffer .= $read;
		} while (strlen($buffer) < $len);

		$data = unserialize($buffer);
		return $data;
	}

	/**
	 * Is the stream open?
	 * @return bool true, in case the stream is open and not eof
	 */
	public function isOpen() {
		return is_resource($this->socket) && !feof($this->socket);
	}



	/**
	 * Get the peer's certificate
	 * @return array the parsed certificate of the peer
	 * @see http://ch1.php.net/manual/de/function.openssl-x509-parse.php
	 */
	public function parsePeerX509Certificate() {
	        $o=$this->getOptions();
		if(!isset($o['ssl']['peer_certificate']) || strpos(@get_resource_type($o['ssl']['peer_certificate']), 'OpenSSL')===false) {
			return array();
		}
		return openssl_x509_parse($o['ssl']['peer_certificate']);
	}

	/**
	 * Get the stream socket options
	 *
	 * array(1) {
	 *   ["ssl"]=>
	 *   array(6) {
	 *     ["disable_compression"]=> bool(true)
	 *     ["capture_peer_cert"]=> bool(true)
	 *     ["capture_peer_cert_chain"]=> bool(true)
	 *     ["verify_peer"]=> bool(false)
	 *     ["peer_certificate"]=> resource(13) of type (OpenSSL X.509)
	 *     ["peer_certificate_chain"]=>
	 *     array(1) {
	 *       [0]=> resource(14) of type (OpenSSL X.509)
	 *     }
	 *   }
	 * }
	 * @return array the socket options
	 * @see http://php.net/manual/de/function.stream-context-get-params.php
	 */
	public function getOptions() {
		return stream_context_get_options($this->socket);
	}
}

