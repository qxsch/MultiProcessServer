<?php
/**
 * Socket Server
 */


namespace QXS\MultiProcessServer;

use QXS\MultiProcessServer\Observers\ObserverInterface,
    QXS\MultiProcessServer\Subjects\SubjectInterface;


class TCPServer implements SubjectInterface {
	protected $socket;
	protected $address='127.0.0.1';
	protected $port;
	protected $backlog=10;
	protected $maxActiveForks=20;
	protected $workerProcesses=array();
	protected $observers;

	/** @var array signals, that should be watched */
	protected $signals = array(
		SIGCHLD, SIGTERM, SIGHUP, SIGUSR1
	);
	
	public function __construct($port, $address = '127.0.0.1') {
		$this->address=(string) $address;
		$this->port=(int)$port;
		$this->observers=new \SplObjectStorage();
	}


	public function validateInt($int, $default) {
		$int=(int)$int;
		if($int<=1) {
			$int=(int)$default;
		}
		return (int)$int;
	}

	
	public function create(ServerWorkerInterface $worker, $backlog=10, $maxActiveForks=20) {
		$this->backlog=$this->validateInt($backlog, 10);
		// /proc/sys/net/core/somaxconn

		$this->maxActiveForks=$this->validateInt($maxActiveForks, 20);

		$this->createSocket();

		// when adding signals use pcntl_signal_dispatch(); or declare ticks
		foreach ($this->signals as $signo) {
			pcntl_signal($signo, array($this, 'signalHandler'));
		}

		$this->notify(ObserverInterface::EV_SERVER_START, array(
			'address' => $this->address,
			'port' => $this->port,
			'backlog' => $this->backlog,
			'maxActiveForks' => $this->maxActiveForks,
			'worker' => $worker,
		));

		$this->serveSocket($worker);
	}

	public function destroy() {
		$this->closeSocket();

		$this->notify(ObserverInterface::EV_SERVER_STOP);
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
				$this->destroy();
				$this->exitPhp(0);
				break;
			case SIGHUP:
				// handle restart tasks
				$this->destroy();
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

			if(isset($this->workerProcesses[$childpid])) {
				unset($this->workerProcesses[$childpid]);

				$this->notify(ObserverInterface::EV_CLIENT_TERMINATED, array(
					'pid' => $childpid,
				));
			}
			
			$childpid = pcntl_waitpid($pid, $status, WNOHANG);
		}
	}



	protected function createSocket() {
		$this->socket=@socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		if($this->socket===false) {
			throw new SocketException('Failed to create the socket. ' . socket_strerror(socket_last_error()));
		}
		
		//socket_set_option($this->socket, SOL_SOCKET, SO_REUSEADDR, 1);

		if(@socket_bind($this->socket, $this->address, $this->port)===false) {
			throw new SocketException('Failed to bind the socket on ' . $this->address . ':' . $this->port . '. ' . socket_strerror(socket_last_error($this->socket)));
		}

		if(@socket_listen($this->socket, $backlog) === false) {
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
		$this->notify(ObserverInterface::EV_SERVER_WAITING_FOR_FREE_FORKS);
		$i=0;
		while(count($this->workerProcesses) >= $this->maxActiveForks && !empty($this->workerProcesses)) {
			$i++;
			pcntl_signal_dispatch();
			usleep(100000);
			// notify every second
			if($i>=10) {
				$this->notify(ObserverInterface::EV_SERVER_WAITING_FOR_FREE_FORKS);
				$i=0;
			}
		}
	}

	protected function waitForIncomingConnection() {
		$this->notify(ObserverInterface::EV_SERVER_WAITING_FOR_INCOMING_CONNECTION);
		$i=0;
		while(true) {
			$i++;
			pcntl_signal_dispatch();
			
			// is the socket ready?
			$rs=array($this->socket); $ws=array(); $es=array();
			$socketsSelected = @socket_select($rs, $ws, $es,  0, 100000);
			if($socketsSelected>=1) {
				return NULL;
			}

			if($i>=10) {
				$this->notify(ObserverInterface::EV_SERVER_WAITING_FOR_INCOMING_CONNECTION);
				$i=0;
			}
		}
		
	}

	protected function serveSocket(ServerWorkerInterface $worker) {
		$address='127.0.0.1';
		$port=0;
		while(true) {
			$this->waitForFreeForks();
			$this->waitForIncomingConnection();

			if(($clientSocket=@socket_accept($this->socket))===false) {
				throw new SocketException('Failed to accept the socket. ' . socket_strerror(socket_last_error($this->socket)));
			}

			// get the data
			socket_getpeername($clientSocket, $address, $port);
			$this->notify(ObserverInterface::EV_SERVER_NEW_INCOMING_CONNECTION, array(
				'address' => $this->address,
				'port' => $this->port,
				'remoteAddress' => $address,
				'remotePort' => $port,
			));

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
				// closing the socket in the parent
				socket_close($clientSocket);

				$this->notify(ObserverInterface::EV_CLIENT_FORKED, array(
					'remoteAddress' => $address,
					'remotePort' => $port,
					'pid' => $processId,
				));
			}
		}
	}


	/**
	 * Update the Observers
	 *
	 * @param \SplSubject $subject   the subject
	 * @param int $eventType  A valid ObserverInterface::EV_* constant
	 * @param array $metadata the meta data for the event
	 * @return \Serializable Returns the result
	 * @throws \Exception in case of a processing Error an Exception will be thrown
	 */
	public function notify($eventType=ObserverInterface::EV_UNKNOWN, array $metaData=array()) {
		foreach($this->observers as $observer) {
			if($observer instanceOf ObserverInterface) {
				$observer->update($this, $eventType, $metaData);
			}
			else {
				$observer->update($this);
			}
		}
	}

	/**
	 * Attach an Observer
	 *
	 * @param \SplObserver $observer  the observer object
	 */
	public function attach(\SplObserver $observer) {
		$this->observers->attach($observer);
	}

	/**
	 * Detach an Observer
	 *
	 * @param \SplObserver $observer  the observer object
	 */
	public function detach(\SplObserver $observer) {
		$this->observers->detach($observer);
	}
}

