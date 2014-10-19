<?php
/**
 * Worker Definition
 */

namespace QXS\MultiProcessServer\Subjects;

/**
 * The Subject Interface to notify the Observers
 */
interface SubjectInterface extends \SplSubject {

	/**
	 * Notify the Observers
	 *
	 * @param \SplSubject $subject   the subject
	 * @param int $eventType  A valid ObserverInterface::EV_* constant
	 * @param array $metadata the meta data for the event
	 * @return \Serializable Returns the result
	 * @throws \Exception in case of a processing Error an Exception will be thrown
	 */
	public function notify($eventType=ObserverInterface::EV_UNKNOWN, array $metaData=array());
}
