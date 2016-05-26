<?php

namespace prime\components;


use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;
use yii\web\HttpException;
use yii\web\IdentityInterface;

trait AuthorizationScopes
{
    private $_operations = [];
    private $_required = false;
    /**
     * @var bool Whether the result is guaranteed to be empty (userCannot when user is admin).
     */
    private $_empty = false;

    public function userCan($operation, $user = null)
    {

        $authManager = app()->authManager;
        $userId = $user instanceof IdentityInterface ? $user->getId() : (is_int($user) ? $user : app()->user->identity->id);

        // Check if we are interested in the current user; and if the current user is admin.
        if (!$authManager->checkAccess($userId,'admin')) {
            $this->_required = true;
            $modelClass = $this->modelClass;
            if (!method_exists($modelClass, 'userCanScope') || !$modelClass::userCanScope($this, $operation, $user)) {
                $this->_operations[] = [$operation, $userId];
            }
        }
        return $this;
    }

    public function userCannot($operation, $user = null)
    {
        $authManager = app()->authManager;
        $userId = $user instanceof IdentityInterface ? $user->getId() : (is_int($user) ? $user : app()->user->identity->id);

        // Check if we are interested in the current user; and if the current user is admin.
        if (!$authManager->checkAccess($userId,'admin')) {
            $this->_required = true;
            $this->_operations[] = [false, $operation, $userId];
        } else {
            $this->_empty = true;
        }
        return $this;
    }

    public function all($db = null)
    {
        if ($this->_empty) {
            return [];
        }
        $results = parent::all($db);
        return empty($this->_operations) ? $results :
            array_filter($results, function($element) {
                foreach ($this->_operations as $params) {
                    if (!$this->checkOperation($element, $params)) {
                        return false;
                    }
                }
                return true;
            });
    }

    /**
     *
     * @param string $q
     * @param null $db
     * @return mixed
     */
    public function count($q = '*', $db = null)
    {
        if ($this->_empty) {
            return 0;
        } elseif (!empty($this->_operations)) {
            if ($q === '*') {
                \Yii::info('Doing inefficient count because userCanScope is not implemented.');
                $clone = clone($this);
                return count($clone->all($db));
            } else {
                throw new InvalidConfigException('Custom counts not supported when using authorizationscopes without userCanScope');
            }
        } else {
            return parent::count($q, $db);
        }
    }

    public function one($db = null)
    {
        if ($this->_empty && $this->_required) {
            throw new HttpException(403, "Operation not allowed");
        } elseif ($this->_empty) {
            return null;
        } elseif (null !== $element = parent::one($db)) {
            foreach($this->_operations as $params) {
                if (!$this->checkOperation($element, $params)) {
                    throw new HttpException(403, "Operation not allowed");
                }
            }
        } elseif ($this->_required) {
            throw new HttpException(403, "Operation not allowed");
        }
        return $element;
    }

    public function column($db = null)
    {
        if($this->select === null || (is_array($this->select) && reset($this->select) === '*')) {
            throw new \Exception('Must specify columns when querying with column()');
        }

        return ArrayHelper::getColumn($this->all(), is_array($this->select) ? reset($this->select) : $this->select);
    }

    private function checkOperation($element, $operation)
    {
        if (count($operation) == 2) {
            array_unshift($operation, true);
        }

        if(!is_bool($userCanResult = $element->userCan($operation[1], $operation[2]))) {
            throw new \Exception('userCan must be boolean, did you implement it in ' . get_class($this) . '?');
        }

        return $operation[0] === $userCanResult;
    }
    /** 
     * From Yii docs:
     * ActiveQuery mainly provides the following methods to retrieve the query results:

        one(): returns a single record populated with the first row of data.
        all(): returns all records based on the query results.
        count(): returns the number of records.
        sum(): returns the sum over the specified column.
        average(): returns the average over the specified column.
        min(): returns the min over the specified column.
        max(): returns the max over the specified column.
        scalar(): returns the value of the first column in the first row of the query result.
        column(): returns the value of the first column in the query result.
        exists(): returns a value indicating whether the query result has data or not.
     * 
     * We should override all of these.
     */
}