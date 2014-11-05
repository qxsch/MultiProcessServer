<?php
/**
 * Worker Definition
 */

namespace QXS\MultiProcessServer\Observers;

/**
 * The Echo Observer to receive updates from the Subjects
 */
class EchoObserver implements ObserverInterface {

	/**
	 * Update the Subjects
	 *
	 * @param \SplSubject $subject   the subject
	 * @param int $eventType  A valid ObserverInterface::EV_* constant
	 * @param array $metadata the meta data for the event
	 * @return null
	 */
	public function update(\SplSubject $subject, $eventType=ObserverInterface::EV_UNKNOWN, array $metaData=array()) {
		echo '['.date('Y-m-d H:i:s').']['.get_class($subject).'] Received an event of type ';
		switch($eventType) {
			// event loop
			case ObserverInterface::EV_SERVER_WAITING_FOR_FREE_FORKS: echo "SERVER WAITING FOR FREE FORKS"; break;
			case ObserverInterface::EV_SERVER_WAITING_FOR_INCOMING_CONNECTION: echo "SERVER WAITING FOR INCOMING CONNECTIONS"; break;
			case ObserverInterface::EV_SERVER_NEW_INCOMING_CONNECTION: echo "SERVER NEW INCOMING CONNECTION"; break;
			case ObserverInterface::EV_SERVER_FAILED_INCOMING_CONNECTION: echo "SERVER FAILED INCOMING CONNECTION"; break;
			case ObserverInterface::EV_CLIENT_FORKED: echo "CLIENT FORKED"; break;
			case ObserverInterface::EV_CLIENT_TERMINATED: echo "CLIENT TERMINATED"; break;
			// startup
			case ObserverInterface::EV_SERVER_IMPERSONATE: echo "SERVER IMPERSONATE"; break;
			case ObserverInterface::EV_SERVER_START: echo "SERVER START"; break;
			case ObserverInterface::EV_SERVER_STOP: echo "SERVER STOP"; break;
			// unknown exception
			default: echo 'UNKNOWN VALUE '.((int)$eventType);
		}
		echo "\n";
		if(!empty($metaData)) {
			var_dump($metaData);
		}
		echo "-------------------\n";
	}
}
