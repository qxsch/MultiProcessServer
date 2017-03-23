<?php
/**
 * Socket Server
 */


namespace QXS\MultiProcessServer;

use QXS\MultiProcessServer\Observers\ObserverInterface,
    QXS\MultiProcessServer\Subjects\SubjectInterface;


class TCPServer implements SubjectInterface {
	protected $socket;
	protected $socketConfig;
	protected $address='127.0.0.1';
	protected $port;
	protected $backlog=10;
	protected $maxActiveForks=20;
	protected $workerProcesses=array();
	protected $observers;
	/**
	 * @see posix_getpwnam()
	 * @var array |null the user, that the server should use
	 */
	protected $runAsUserInfo=null;

	/** @var array signals, that should be watched */
	protected $signals = array(
		SIGCHLD, SIGTERM, SIGHUP, SIGUSR1
	);
	
	public function __construct($port, $address = '127.0.0.1', SocketStreamConfiguration $socketConfig=null) {
		$this->address=(string) $address;
		$this->port=(int)$port;
		$this->observers=new \SplObjectStorage();
		if($socketConfig===null) {
			$this->socketConfig=new SocketStreamConfiguration();
		}
		else {
			$this->socketConfig=$socketConfig;
		}
		// check the configuration and throw an exception in case of an error
		$this->socketConfig->checkConfiguration(true);
	}


	protected function validateInt($int, $default) {
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

		if(
			isset($this->runAsUserInfo['uid']) &&
			isset($this->runAsUserInfo['gid'])
		) {
			if(
				posix_setegid($this->runAsUserInfo['gid']) &&
				posix_seteuid($this->runAsUserInfo['uid'])
			) {
				$this->notify(ObserverInterface::EV_SERVER_IMPERSONATE, $this->runAsUserInfo);
			}
			else {
				$this->closeSocket();
				throw new ImpersonationException('Cannot switch to user "'.$this->runAsUserInfo['name'].'"');
			}
		}

		$this->notify(ObserverInterface::EV_SERVER_STARTED, array(
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

		$this->notify(ObserverInterface::EV_SERVER_STOPPED);
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
		$options=$this->socketConfig->getContextConfiguration();
		$options['socket']['bindto'] = $this->address . ':' . $this->port;
		$options['socket']['backlog'] = $this->backlog;
		// creating the context
		$context=stream_context_create($options);

		$this->socket=stream_socket_server($this->socketConfig->getProtocol().'://'.$this->address . ':' . $this->port, $errno, $errstr, STREAM_SERVER_BIND|STREAM_SERVER_LISTEN, $context);
		if($this->socket===false) {
			throw new SocketException('Failed to create the socket. ' . $errstr, $errno);
		}
	}

	protected function closeSocket() {
		fclose($this->socket);
	}
	
	protected function exitPhp() {
		exit();
	}

	/**
	 * Waits until more forks can be forked
	 * Observers are just being notified in case, the server is waiting for returning children
	 */
	protected function waitForFreeForks() {
		$i=10;
		while(count($this->workerProcesses) >= $this->maxActiveForks && !empty($this->workerProcesses)) {
			pcntl_signal_dispatch();
			// notify every second
			if($i>=10) {
				$this->notify(ObserverInterface::EV_SERVER_WAITING_FOR_FREE_FORKS);
				$i=0;
			}
			pcntl_signal_dispatch();
			usleep(100000);
			$i++;
		}
	}

	/**
	 * Waits until there is a new connection
	 * Observers are just being notified in case, the server is waiting for a connection
	 */
	protected function waitForIncomingConnection() {
		$i=10;
		while(true) {
			pcntl_signal_dispatch();
			
			// is the socket ready?
			$rs=array($this->socket); $ws=array(); $es=array();
			$socketsSelected = @stream_select($rs, $ws, $es,  0, 100000);
			if($socketsSelected>=1) {
				return NULL;
			}

			if($i>=10) {
				$this->notify(ObserverInterface::EV_SERVER_WAITING_FOR_INCOMING_CONNECTION);
				$i=0;
			}
			$i++;
		}
		
	}

	protected function serveSocket(ServerWorkerInterface $worker) {
		$address='127.0.0.1';
		$port=0;
		while(true) {
			$this->waitForIncomingConnection();
			$this->waitForFreeForks();

			$clientSocket=@stream_socket_accept($this->socket, 60, $address);
			if($clientSocket===false) {
				// notify the observers
				$this->notify(ObserverInterface::EV_SERVER_FAILED_INCOMING_CONNECTION, array(
					'address' => $this->address,
					'port' => $this->port,
				));
				continue;
			}

			// split address:port string
			$address=explode(':', $address);
			$port=array_pop($address);
			$address=implode(':', $address);

			// notify the observers
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
					new SimpleSocketStream($clientSocket)
				);
				$worker->onProcessDestroy();
				$this->exitPhp();
			} else {
				// WE ARE IN THE PARENT
				$this->workerProcesses[$processId]=true;
				// closing the socket in the parent
				fclose($clientSocket);

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
	 * @param int $eventType  A valid ObserverInterface::EV_* constant
	 * @param array $metadata the meta data for the event
	 * @return null
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
	 * @return null
	 */
	public function attach(\SplObserver $observer) {
		$this->observers->attach($observer);
	}

	/**
	 * Detach an Observer
	 *
	 * @param \SplObserver $observer  the observer object
	 * @return null
	 */
	public function detach(\SplObserver $observer) {
		$this->observers->detach($observer);
	}

	/**
	 * Sets the user the server should use
	 *
	 * @param string $user  set the user, that the server should use
	 */
	public function runAsUser($user) {
		$info=posix_getpwnam($user);
		if($info===false || !is_array($info)) {
			throw new \DomainException('Invalid username. The user "'.$user.'" does not exist.');
		}
		$this->runAsUserInfo=$info;

		return $this;
	}
}

