<?php
/**
 * Worker Definition
 */

namespace QXS\MultiProcessServer\Observers;

/**
 * The Observer Interface to receive updates from the Subjects
 */
interface ObserverInterface extends \SplObserver {

	const EV_UNKNOWN=0;
	const EV_SERVER_START=1;
	const EV_SERVER_SHUTDOWN=2;
	const EV_SERVER_WAITING_FOR_FREE_FORKS=3;
	const EV_SERVER_WAITING_FOR_INCOMING_CONNECTION=4;
	const EV_SERVER_NEW_INCOMING_CONNECTION=5;
	const EV_CLIENT_FORKED=6;
	const EV_CLIENT_TERMINATED=7;


	/**
	 * Update the Subjects
	 *
	 * @param \SplSubject $subject   the subject
	 * @param int $eventType  A valid ObserverInterface::EV_* constant
	 * @param array $metadata the meta data for the event
	 * @return \Serializable Returns the result
	 * @throws \Exception in case of a processing Error an Exception will be thrown
	 */
	public function update(\SplSubject $subject, $eventType=ObserverInterface::EV_UNKNOWN, array $metaData=array());
}