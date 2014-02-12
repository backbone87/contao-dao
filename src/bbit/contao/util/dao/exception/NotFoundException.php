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
class NotFoundException extends DAOException {

	/**
	 * @param DAO $dao
	 * @param string $message
	 * @param integer $code
	 * @param \Exception $previous
	 * @return DAOException
	 */
	public static function create(DAO $dao, $message = null, $code = 1, \Exception $previous = null) {
		$message === null && $message = 'DAO ' . $dao . ' not found (gone)';
		$exception = parent::create($dao, $message, $code, $previous);
		return $exception;
	}

	/**
	 * @param string $message
	 * @param integer $code
	 * @param \Exception $previous
	 */
	public function __construct($message, $code = null, $previous = null) {
		parent::__construct($message, $code, $previous);
	}

}
