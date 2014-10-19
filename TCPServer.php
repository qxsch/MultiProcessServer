<?php
/**
 * Socket Server
 */


namespace QXS\MultiProcessServer;


class TCPServer {
	protected $socket;
	protected $address='127.0.0.1';
	protected $port;
	protected $backlog=10;
	protected $maxActiveForks=20;
	protected $workerProcesses=array();

	/** @var array signals, that should be watched */
	protected $signals = array(
		SIGCHLD, SIGTERM, SIGHUP, SIGUSR1
	);
	
	public function __construct($port, $address = '127.0.0.1') {
		$this->address=(string) $address;
		$this->port=(int)$port;
	}


	public function validateInt($int, $default) {
		$int=(int)$int;
		if($int<=1) {
			$int=(int)$default;
		}
		return (int)$int;
	}

	
	public function create(ServerWorkerInterface $worker, $backlog=10, $maxActiveForks=20) {
		$this->backlog=$this->validateInt($backlog);
		// /proc/sys/net/core/somaxconn

		$this->maxActiveForks=$this->validateInt($maxActiveForks);
		if($backlog<=1) {
			$backlog=10;
		}
		$this->backlog=$backlog;

		$this->createSocket();

		$this->serveSocket($worker);

		// when adding signals use pcntl_signal_dispatch(); or declare ticks
		foreach ($this->signals as $signo) {
			pcntl_signal($signo, array($this, 'signalHandler'));
		}
	}

	public function destroy() {
		$this->closeSocket();
	}
	
	/**
	 * Receives signals
	 *
	 * DO NOT MANUALLY CALL THIS METHOD!
	 * pcntl_signal_dispatch() will be calling this method.
	 * @param int $signo the signal number
	 * @see pcntl_signal_dispatch
	 * @see pcntl_signal
	 */
	public function signalHandler($signo) {
		switch ($signo) {
			case SIGCHLD:
				$this->reaper();
				break;
			case SIGTERM:
				// handle shutdown tasks
				$this->exitPhp(0);
				break;
			case SIGHUP:
				// handle restart tasks
				break;
			case SIGUSR1:
				// handle sigusr
				break;
			default: // handle all other signals
		}
		// more signals to dispatch?
		pcntl_signal_dispatch();
	}

	/**
	 * Child process reaper
	 * @param int $pid the process id
	 * @see pcntl_waitpid
	 */
	protected function reaper($pid = -1) {
		if (!is_int($pid)) {
			$pid = -1;
		}
		$childpid = pcntl_waitpid($pid, $status, WNOHANG);
		while ($childpid > 0) {
			$stopSignal = pcntl_wstopsig($status);
			if(pcntl_wifexited($stopSignal) === FALSE) {
				// abnormal signal received
			}
			
			unset($this->workerProcesses[$childpid]);
			
			$childpid = pcntl_waitpid($pid, $status, WNOHANG);
		}
	}



	protected function createSocket() {
		$this->socket=socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		if($this->socket===false) {
			throw new SocketException('Failed to create the socket. ' . socket_strerror(socket_last_error()));
		}
		
		//socket_set_option($this->socket, SOL_SOCKET, SO_REUSEADDR, 1);

		if(socket_bind($this->socket, $this->address, $this->port)===false) {
			throw new SocketException('Failed to bind the socket on ' . $this->address . ':' . $this->port . '. ' . socket_strerror(socket_last_error($this->socket)));
		}

		if(socket_listen($this->socket, $backlog) === false) {
			throw new SocketException('Failed to listen on the socket with backlog ' . $backlog . '. ' . socket_strerror(socket_last_error($this->socket )));
		}
	}

	protected function closeSocket() {
		socket_close($this->socket);
	}
	
	protected function exitPhp() {
		exit();
	}

	protected function waitForFreeForks() {
		while(count($this->workerProcesses) >= $this->maxActiveForks || empty($this->workerProcesses)) {
			pcntl_signal_dispatch();
			usleep(10000);
		}
	}

	protected function serveSocket(ServerWorkerInterface $worker) {
		while(true) {
			$this->waitForFreeForks();
			
			if(($clientSocket=@socket_accept($this->socket))===false) {
				throw new SocketException('Failed to accept the socket. ' . socket_strerror(socket_last_error($this->socket)));
			}
				
			// fork a process
			$processId = pcntl_fork();
			if ($processId < 0) {
				// cleanup using posix_kill & pcntl_wait
				throw new \RuntimeException('pcntl_fork failed.');
				break;
			} elseif ($processId === 0) {
				// WE ARE IN THE CHILD
				$worker->onProcessCreate();
				$worker->serveClient(
					new SimpleSocket($clientSocket)
				);
				$worker->onProcessDestroy();
				$this->exitPhp();
			} else {
				// WE ARE IN THE PARENT
				$this->workerProcesses[$processId]=true;
			}
		}
	}

}

