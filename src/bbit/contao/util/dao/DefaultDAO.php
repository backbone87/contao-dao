<?php

/**
 * @copyright	backboneIT | Oliver Hoff 2013
 * @author		Oliver Hoff <oliver@hofff.com>
 * @license		commercial
 */

namespace bbit\contao\util\dao;

use bbit\contao\util\dao\exception\LockedException;
use bbit\contao\util\dao\exception\NotFoundException;
use bbit\contao\util\dao\exception\NotNewException;
use bbit\contao\util\dao\exception\OutOfSyncException;

/**
 * @author Oliver Hoff <oliver@hofff.com>
 */
class DefaultDAO implements DAO {

    /**
     *
     * @param type $table
     * @param array $data
     * @param type $pkColumn
     * @param type $tsColumn
     * @param type $optimisticLockingColumn
     * @param type $initialOptimisticLockingValue
     * @param type $pessimisticLockingColumn
     * @param type $defaultPessimisticLockingTimeout
     * @return \bbit\contao\util\dao\DefaultDAO
     */
	public static function create(
			$table,
			array $data = null,
			$pkColumn = 'id',
			$tsColumn = 'tstamp',
			$optimisticLockingColumn = 'version',
			$initialOptimisticLockingValue = 0,
			$pessimisticLockingColumn = 'locked',
			$defaultPessimisticLockingTimeout = 5) {
		$dao = new static;
		$dao->setTable($table);
		$dao->setData($data);
		$dao->setPKColumn($pkColumn);
		$dao->setTSColumn($tsColumn);
		$dao->setOptimisticLockingColumn($optimisticLockingColumn);
		$dao->setInitialOptimisticLockingValue($initialOptimisticLockingValue);
		$dao->setPessimisticLockingColumn($pessimisticLockingColumn);
		$dao->setDefaultPessimisticLockingTimeout($defaultPessimisticLockingTimeout);
		return $dao;
	}

	private $data;

	private $table;

	private $pkColumn = 'id';

	private $tsColumn = 'tstamp';

	private $optimisticLockingColumn = 'version';

	private $initialOptimisticLockingValue = 0;

	private $pessimisticLockingColumn = 'lockTime';

	private $defaultPessimisticLockingTimeout = 5;

	protected function __construct() {
	}

	public function __toString() {
		return $this->toString();
	}

	public function __get($key) {
		return $this->get($key);
	}

	public function __set($key, $value) {
		$this->set($key, $value);
	}

	public function __isset($key) {
		return $this->has($key);
	}

	public function __unset($key) {
		return $this->remove($key);
	}

	public function __clone() {
		$this->remove($this->getPKColumn());
	}

	public function toString() {
		$pkColumn = $this->getPKColumn();
		return $this->$pkColumn === null ? 'Unsaved object ' . spl_object_hash($this) : '#' . $this->$pkColumn;
	}

	public function fork() {
		return clone $this;
	}

	public function get($key) {
		return $this->data[$key];
	}

	public function set($key, $value) {
		$this->data[$key] = $value;
	}

	public function has($key) {
		return isset($this->data[$key]);
	}

	public function remove($key) {
		unset($this->data[$key]);
	}

	public function getData() {
		return $this->data;
	}

	public function setData(array $data = null) {
		$this->data = (array) $data;
	}

	public function addData(array $data = null) {
		$data && $this->setData(array_merge($this->getData(), $data));
	}

	public function getPK() {
		return $this->get($this->getPKColumn());
	}

	public function isNew() {
		return $this->getPK() === null;
	}

	public function requireNew() {
		if(!$this->isNew()) {
			throw NotNewException::create($this);
		}
	}

	public function isSynced(&$optimisticLockingValue = null) {
		if($this->isNew() || !$this->hasOptimisticLockingColumn()) {
			return true;
		}
		$pkColumn = $this->getPKColumn();
		$optimisticLockingColumn = $this->getOptimisticLockingColumn();
		$sql = 'SELECT ' . $optimisticLockingColumn . ' AS optimisticLockingValue FROM ' . $this->getTable() . ' WHERE ' . $pkColumn . ' = ?';
		$optimisticLockingValue = \Database::getInstance()->prepare($sql)->executeUncached($this->$pkColumn)->optimisticLockingValue;
		return $this->$optimisticLockingColumn == $optimisticLockingValue;
	}

	public function requireSynced() {
		if(!$this->isSynced($optimisticLockingValue)) {
			throw $optimisticLockingValue === null
				? NotFoundException::create($this)
				: OutOfSyncException::create($this, $optimisticLockingValue);
		}
	}

	public function isLocked() {
		$column = $this->getPessimisticLockingColumn();
		return $this->$column !== null && $this->$column > time();
	}

	public function aquireLock($force = false, $timeout = null) {
		if(!$force && $this->isLocked()) {
			throw LockedException::create($this);
		}
		$column = $this->getPessimisticLockingColumn();
		$timeout === null && $timeout = $this->getDefaultPessimisticLockingTimeout();
		$this->$column = time() + $timeout;
		$this->save();
	}

	public function releaseLock() {
		$column = $this->getPessimisticLockingColumn();
		$this->$column = null;
		$this->save();
	}

	public function update() {
		if($this->isNew()) {
			return;
		}
		$pkColumn = $this->getPKColumn();
		$sql = 'SELECT * FROM ' . $this->getTable() . ' WHERE ' . $pkColumn . ' = ?';
		$result = \Database::getInstance()->prepare($sql)->executeUncached($this->$pkColumn);
		if($result->numRows) {
			$this->addData($result->row());
		} else {
			throw NotFoundException::create($this, $pkColumn, $this->$pkColumn);
		}
		return $this;
	}

	public function save() {
		$table = $this->getTable();
		$pkColumn = $this->getPKColumn();
		$this->hasTSColumn() && $tsColumn = $this->getTSColumn();
		$this->hasOptimisticLockingColumn() && $optimisticLockingColumn = $this->getOptimisticLockingColumn();

		$data = array_intersect_key($this->getData(), array_flip($this->getColumns()));

		unset($data[$pkColumn]);
		$tsColumn && $data[$tsColumn] = time();

		if($this->isNew()) {
			$optimisticLockingColumn && $data[$optimisticLockingColumn] = $this->getInitialOptimisticLockingValue();
			$this->$pkColumn = \Database::getInstance()->prepare(
				'INSERT INTO ' . $table . ' %s'
			)->set($data)->executeUncached()->insertId;
			$optimisticLockingColumn && $this->$optimisticLockingColumn = $this->getInitialOptimisticLockingValue();
			return $this;
		}

		if($optimisticLockingColumn) {
			unset($data[$optimisticLockingColumn]);
		}

		$where = ' WHERE ' . $pkColumn . ' = ?';
		$params[] = $this->$pkColumn;

		if($optimisticLockingColumn) {
			$set = ', ' . $optimisticLockingColumn . ' = ' . $optimisticLockingColumn . ' + 1';
			$where .= ' AND ' . $optimisticLockingColumn . ' = ?';
			$params[] = $this->$optimisticLockingColumn;
		}

		$sql = 'UPDATE ' . $table . ' %s' . $set . $where;
		$result = \Database::getInstance()->prepare($sql)->set($data)->executeUncached($params);

		if($result->affectedRows) {
			// a locking column exists, so we must update it
			$optimisticLockingColumn && $this->$optimisticLockingColumn = $this->$optimisticLockingColumn + 1;

		} elseif($optimisticLockingColumn) {
			$this->requireSynced(); // will throw exception, if an update failed because PK gone or out of sync

		} else {
			$sql = 'SELECT ' . $pkColumn . ' FROM ' . $table . ' WHERE ' . $pkColumn . ' = ?';
			$result = \Database::getInstance()->prepare($sql)->executeUncached($this->$pkColumn);

			if(!$result->numRows) { // PK gone
				throw GoneException::create($this);
			}
		}

		return $this;
	}

	public function getTable() {
		return $this->table;
	}

	protected function setTable($table) {
		$this->table = $table;
	}

	public function getColumns() {
		return \Database::getInstance()->getFieldNames($this->getTable());
	}

	public function getPKColumn() {
		return $this->pkColumn;
	}

	protected function setPKColumn($pkColumn) {
		$this->pkColumn = $pkColumn;
	}

	public function hasTSColumn() {
		return in_array($this->tsColumn, $this->getColumns());
	}

	public function getTSColumn() {
		if(!$this->hasTSColumn()) {
			throw new \RuntimeException('DAO ' . $this->toString() . ' has no timestamp column', 1);
		}
		return $this->tsColumn;
	}

	protected function setTSColumn($tsColumn) {
		$this->tsColumn = $tsColumn;
	}

	public function hasOptimisticLockingColumn() {
		return in_array($this->optimisticLockingColumn, $this->getColumns());
	}

	public function getOptimisticLockingColumn() {
		if(!$this->hasOptimisticLockingColumn()) {
			throw new \RuntimeException('DAO ' . $this->toString() . ' has no optimistic locking feature', 1);
		}
		return $this->optimisticLockingColumn;
	}

	protected function setOptimisticLockingColumn($optimisticLockingColumn) {
		$this->optimisticLockingColumn = $optimisticLockingColumn;
	}

	public function getInitialOptimisticLockingValue() {
		return $this->initialOptimisticLockingValue;
	}

	protected function setInitialOptimisticLockingValue($initialOptimisticLockingValue) {
		$this->initialOptimisticLockingValue = $initialOptimisticLockingValue;
	}

	public function hasPessimisticLockingColumn() {
		return in_array($this->pessimisticLockingColumn, $this->getColumns());
	}

	public function getPessimisticLockingColumn() {
		if(!$this->hasOptimisticLockingColumn() || !$this->hasPessimisticLockingColumn()) {
			throw new \RuntimeException('DAO ' . $this->toString() . ' has no pessimistic locking feature', 1);
		}
		return $this->pessimisticLockingColumn;
	}

	protected function setPessimisticLockingColumn($pessimisticLockingColumn) {
		$this->pessimisticLockingColumn = $pessimisticLockingColumn;
	}

	public function getDefaultPessimisticLockingTimeout() {
		return $this->defaultPessimisticLockingTimeout;
	}

	protected function setDefaultPessimisticLockingTimeout($defaultPessimisticLockingTimeout) {
		$this->defaultPessimisticLockingTimeout = $defaultPessimisticLockingTimeout;
	}

	protected function lockTable($read = false) {
		\Database::getInstance()->lockTables(array($this->getTable() => $read ? 'READ' : 'WRITE'));
	}

	protected function unlockTable() {
		\Database::getInstance()->unlockTables();
	}

}
