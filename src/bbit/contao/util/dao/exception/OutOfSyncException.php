<?php

/**
 * @copyright	backboneIT | Oliver Hoff 2013
 * @author		Oliver Hoff <oliver@hofff.com>
 * @license		commercial
 */

namespace bbit\contao\util\dao\exception;

use bbit\contao\util\dao\DAO;

/**
 * @author Oliver Hoff <oliver@hofff.com>
 */
class OutOfSyncException extends DAOException {

	/**
	 * @param DAO $dao
	 * @param mixed $lockingValue
	 * @param string $message
	 * @param integer $code
	 * @param \Exception $previous
	 * @return DAOException
	 */
	public static function create(DAO $dao, $lockingValue, $message = null, $code = 1, \Exception $previous = null) {
		$message === null && $message = 'DAO ' . $dao . ' is out of sync. Latest locking value is ' . $lockingValue;
		$exception = parent::create($dao, $message, $code, $previous);
		$this->setLatestLockingValue($lockingValue);
		return $exception;
	}

	/** @var mixed */
	private $lockingValue;

	/**
	 * @param string $message
	 * @param integer $code
	 * @param \Exception $previous
	 */
	public function __construct($message, $code = null, $previous = null) {
		parent::__construct($message, $code, $previous);
	}

	/**
	 * @return mixed
	 */
	public function getLatestLockingValue() {
		return $this->lockingValue;
	}

	/**
	 * @param mixed $lockingValue
	 * @return void
	 */
	protected function setLatestLockingValue($lockingValue) {
		$this->lockingValue = $lockingValue;
	}

}
