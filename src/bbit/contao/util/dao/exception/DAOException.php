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
class DAOException extends \RuntimeException {

	/**
	 * @param DAO $dao
	 * @param string $message
	 * @param integer $code
	 * @param \Exception $previous
	 * @return DAOException
	 */
	public static function create(DAO $dao, $message = null, $code = 1, \Exception $previous = null) {
		$message === null && $message = 'operation on DAO ' . $dao . ' failed';
		$exception = new static($message, $code, $previous);
		$exception->setDAO($dao);
		return $exception;
	}

	/** @var DAO */
	private $dao;

	/**
	 * @param string $message
	 * @param integer $code
	 * @param \Exception $previous
	 */
	public function __construct($message, $code = null, $previous = null) {
		parent::__construct($message, $code, $previous);
	}

	/**
	 * @return DAO
	 */
	public function getDAO() {
		return $this->dao;
	}

	/**
	 * @param DAO $dao
	 * @return void
	 */
	protected function setDAO(DAO $dao) {
		$this->dao = $dao;
	}

}
