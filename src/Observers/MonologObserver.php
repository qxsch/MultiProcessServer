<?php
/**
 * Worker Definition
 */

namespace QXS\MultiProcessServer\Observers;

use Monolog\Logger;

/**
 * The Monolog Observer to receive updates from the Subjects
 */
class MonologObserver implements ObserverInterface {
	private $logger;

	/**
	 * Creates a Monolog Observer
	 */
	public function __construct(Logger $logger) {
		$this->logger = $logger;
	}

	/**
	 * Update the Subjects
	 *
	 * @param \SplSubject $subject   the subject
	 * @param int $eventType  A valid ObserverInterface::EV_* constant
	 * @param array $metadata the meta data for the event
	 * @return null
	 */
	public function update(\SplSubject $subject, $eventType=ObserverInterface::EV_UNKNOWN, array $metaData=array()) {
		switch($eventType) {
			// event loop
			case ObserverInterface::EV_SERVER_WAITING_FOR_FREE_FORKS: $this->logger->info("Server is waiting for free forks.", $metaData); break;
			case ObserverInterface::EV_SERVER_WAITING_FOR_INCOMING_CONNECTION: $this->logger->info("Server is waiting for incoming connections", $metaData); break;
			case ObserverInterface::EV_SERVER_NEW_INCOMING_CONNECTION: $this->logger->info("Server received an incoming connection", $metaData); break;
			case ObserverInterface::EV_SERVER_FAILED_INCOMING_CONNECTION: $this->logger->error("Server failed on incoming connection", $metaData); break;
			case ObserverInterface::EV_CLIENT_FORKED: $this->logger->info("Client forked", $metaData); break;
			case ObserverInterface::EV_CLIENT_TERMINATED: $this->logger->info("Client terminated", $metaData); break;
			// startup
			case ObserverInterface::EV_SERVER_IMPERSONATE: $this->logger->info("Server impersonated", $metaData); break;
			case ObserverInterface::EV_SERVER_STARTED: $this->logger->info("Server started", $metaData); break;
			case ObserverInterface::EV_SERVER_STOPPED: $this->logger->info("Server stopped", $metaData); break;
			// unknown exception
			default: $this->logger->warning('Unknown value received: '.((int)$eventType), $metaData);
		}
	}
}
