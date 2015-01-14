<?php
/**
 * @copyright wiseape GmbH
 * @author Ruben Rögels <ruben.roegels@wiseape.de>
 * @license MIT
 */

namespace bbit\contao\util\dao;

use bbit\contao\util\dao\exception\MethodAccessDeniedException;
use bbit\contao\util\dao\exception\FieldAccessDeniedException;
use bbit\contao\util\dao\exception\DaoLockedException;
use bbit\contao\util\dao\DAO;

class DAOProxy {
    /**
     * @var array
     */
    private $allowedMethods = array();
    
    /**
     * @var type 
     */
    private $bypassMethodCheck = false;
    
    /**
     * @var array
     */
    private $allowedFields = array();
    
    /**
     * @var boolean
     */
    private $bypassFieldCheck = false;
    
    /**
     * @var boolean
     */
    private $locked = false;
    
    /**
     * @var \bbit\contao\util\dao\DAO 
     */
    private $dao = null;
    
    private function __construct(DAO $dao) {
        $this->dao = $dao;
    }
    
    public function __clone() {
        $this->dao = clone $this->dao;
    }

    /**
     * @param string $name
     * @param array $arguments
     * @return mixed
     * @throws \bbit\contao\util\dao\exception\MethodAccessDeniedException
     * @throws \bbit\contao\util\dao\exception\FieldAccessDeniedException
     */
    public function __call($name, array $arguments) {
        // check if method name is in allowed methods list
        if(!in_array($name, $this->allowedMethods)
                && !$this->bypassMethodCheck) {
            throw new MethodAccessDeniedException('Access to method "'.$name.'" denied.');
        }
        
        // check field access
        if(in_array($name,array('get','set'))
                && !$this->bypassFieldCheck) {
            $key = $arguments[0];
            if(!in_array($key, $this->allowedFields)) {
                throw new FieldAccessDeniedException('Access to field "'.$key.'" denied.');
            }
        }
        
        return call_user_func_array(array($this->dao,$name), $arguments);
    }
    
    /**
     * @param \bbit\contao\util\dao\DAO $dao
     * @return \bbit\contao\util\dao\DAOProxy;
     */
    public static function create(DAO $dao) {
        return new static($dao);
    }
    
    /**
     * @param string $field
     * @return \bbit\contao\util\dao\DAOProxy
     */
    public function addAllowedField($field) {
        if($this->isLocked()) {
            throw new DaoLockedException('Cannot add field "'.$field.'" because '.__CLASS__.' is locked.');
        }
        $this->allowedFields[] = $field;
        return $this;
    }
    
    /**
     * @param string $method
     * @return \bbit\contao\util\dao\DAOProxy
     */
    public function addAllowedMethod($method) {
        if($this->isLocked()) {
            throw new DaoLockedException('Cannot add method "'.$method.'" because '.__CLASS__.' is locked.');
        }
        $this->allowedMethods[] = $method;
        return $this;
    }
    
    /**
     * @return boolean
     */
    public function isLocked() {
        return $this->locked;
    }
    
    /**
     * @return \bbit\contao\util\dao\DAOProxy
     */
    public function lock() {
        /**
         * @todo the same method name exists in the DefaultDAO
         */
        $this->locked = true;
        return $this;
    }
    
    /**
     * @param array $fields
     * @return \bbit\contao\util\dao\DAOProxy
     */
    public function setAllowedFiles(array $fields) {
        if($this->isLocked()) {
            throw new DaoLockedException('Cannot set fields because "'.__CLASS__.'" is locked.');
        }
        $this->allowedFields = $fields;
        return $this;
    }
    
    /**
     * @param array $methods
     * @return \bbit\contao\util\dao\DAOProxy
     * @throws \bbit\contao\util\dao\exception\DaoLockedException
     */
    public function setAllowedMethods(array $methods) {
        if($this->isLocked()) {
            throw new DaoLockedException('Cannot set methods because "'.__CLASS__.'" is locked.');
        }
        $this->allowedMethods = $methods;
        return $this;
    }
    
    /**
     * @param boolean $check
     * @return \bbit\contao\util\dao\DAOProxy
     * @throws \WiseapeContao\Util\Exception\DaoLockedException
     */
    public function setBypassFieldCheck($check) {
        if($this->isLocked()) {
            throw new DaoLockedException('Cannot bypass field check because "'.__CLASS__.'" is locked.');
        }
        $this->bypassFieldCheck = (boolean)$check;
        return $this;
    }
    
    /**
     * @param boolean $check
     * @return \bbit\contao\util\dao\DAOProxy
     * @throws \WiseapeContao\Util\Exception\DaoLockedException
     */
    public function setBypassMethodCheck($check) {
        if($this->isLocked()) {
            throw new DaoLockedException('Cannot bypass method check because "'.__CLASS__.'" is locked.');
        }
        $this->bypassMethodCheck = (boolean)$check;
        return $this;
    }
}
