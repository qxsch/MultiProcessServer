<?php
/**
 * Worker Definition
 */

namespace QXS\MultiProcessServer;


/**
 * The Closure Server Worker Class
 */
class ClosureServerWorker implements ServerWorkerInterface {

	/** @var \Closure Closure that runs the task */
	protected $create;

	/** @var \Closure Closure that will be used to serve the client, when a server worker has been forked */
	protected $serveClient;

	/** @var \Closure Closure that will be used before a worker is getting destroyed */
	protected $destroy;

	/**
	 * The constructor
	 * @param \Closure $serveClient Closure that serves the client
	 * @param \Closure $create Closure that can be used when a worker has been forked
	 * @param \Closure $destroy Closure that can be used before a worker is getting destroyed
	 */
	public function __construct(\Closure $serveClient, \Closure $create = NULL, \Closure $destroy = NULL) {
		if(is_null($create)) {
			$create=function() { };
		}
		if(is_null($destroy)) {
			$destroy=function() { };
		}
		$this->create = $create;
		$this->serveClient = $serveClient;
		$this->destroy = $destroy;
	}

	/**
	 * After the worker has been forked into another process
	 *
	 * @throws \Exception in case of a processing Error an Exception will be thrown
	 */
	public function onProcessCreate() {
		$this->create->__invoke();
	}

	/**
	 * Before the worker process is getting destroyed
	 *
	 * @throws \Exception in case of a processing Error an Exception will be thrown
	 */
	public function onProcessDestroy() {
		$this->destroy->__invoke();
	}

	/**
	 * Serve the client
	 *
	 * @param \QXS\WorkerPool\SimpleSocket $simpleSocket the communication socket
	 * @return \Serializable Returns the result
	 * @throws \Exception in case of a processing Error an Exception will be thrown
	 */
	public function serveClient(\QXS\MultiProcessServer\SimpleSocket $simpleSocket) {
		return $this->serveClient->__invoke($simpleSocket);
	}
}

