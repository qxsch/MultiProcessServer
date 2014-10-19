<?php
/**
 * Worker Definition
 */

namespace QXS\MultiProcessServer;

/**
 * The Interface for worker processes
 */
interface ServerWorkerInterface {

	/**
	 * After the worker has been forked into another process
	 *
	 * @throws \Exception in case of a processing Error an Exception will be thrown
	 */
	public function onProcessCreate();

	/**
	 * Before the worker process is getting destroyed
	 *
	 * @throws \Exception in case of a processing Error an Exception will be thrown
	 */
	public function onProcessDestroy();

	/**
	 * Serve the client
	 *
	 * @param \QXS\WorkerPool\SimpleSocket $simpleSocket the communication socket
	 * @return \Serializable Returns the result
	 * @throws \Exception in case of a processing Error an Exception will be thrown
	 */
	public function serveClient(\QXS\MultiProcessServer\SimpleSocket $simpleSocket);
}

