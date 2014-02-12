<?php

/**
 * @copyright	backboneIT | Oliver Hoff 2013
 * @author		Oliver Hoff <oliver@hofff.com>
 * @license		commercial
 */

namespace bbit\contao\util\dao;

/**
 * @author Oliver Hoff <oliver@hofff.com>
 */
interface DAO {

	public function __toString();

	public function __get($key);

	public function __set($key, $value);

	public function __isset($key);

	public function __unset($key);

	public function __clone();

	public function toString();

	public function fork();

	public function get($key);

	public function set($key, $value);

	public function has($key);

	public function remove($key);

	public function getData();

	public function setData(array $data = null);

	public function addData(array $data = null);

	public function getPK();

	public function isNew();

	public function requireNew();

	public function isSynced(&$optimisticLockingValue = null);

	public function requireSynced();

	public function isLocked();

	public function aquireLock($force = false, $timeout = null);

	public function releaseLock();

	public function update();

	public function save();

	public function getTable();

	public function getColumns();

	public function getPKColumn();

	public function hasTSColumn();

	public function getTSColumn();

	public function hasOptimisticLockingColumn();

	public function getOptimisticLockingColumn();

	public function getInitialOptimisticLockingValue();

	public function hasPessimisticLockingColumn();

	public function getPessimisticLockingColumn();

	public function getDefaultPessimisticLockingTimeout();

}
