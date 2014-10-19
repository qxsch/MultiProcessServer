<?php
/**
 * Worker Definition
 */

namespace QXS\MultiProcessServer\Observers;

/**
 * The Null Observer to receive updates from the Subjects
 */
class NullObserver implements ObserverInterface {

	/**
	 * Update the Subjects
	 *
	 * @param \SplSubject $subject   the subject
	 * @param int $eventType  A valid ObserverInterface::EV_* constant
	 * @param array $metadata the meta data for the event
	 * @return \Serializable Returns the result
	 * @throws \Exception in case of a processing Error an Exception will be thrown
	 */
	public function update(\SplSubject $subject, $eventType=ObserverInterface::EV_UNKNOWN, array $metaData=array()) {
	}
}
