<?php
namespace liuguang\mvc;

use liuguang\mvc\exceptions\ContainerException;

class ObjectContainer
{

    const TYPE_NAME_MAP = 0;

    const TYPE_CALLABLE_MAP = 1;

    const TYPE_OBJECT_MAP = 2;

    private $relationMap;

    private $instanceMap;

    private $aliasMap;

    public function __construct()
    {
        $this->relationMap = [];
        $this->instanceMap = [];
        $this->aliasMap = [];
    }

    private function getRelationKey(string $interfaceName, int $implIndex): string
    {
        return $interfaceName . '#' . $implIndex;
    }

    public function addNameMap(string $interfaceName, string $implName = '', string $alias = '', bool $isSingleton = true, int $implIndex = 0)
    {
        $relationKey = $this->getRelationKey($interfaceName, $implIndex);
        if (! isset($this->relationMap[$relationKey])) {
            $this->relationMap[$relationKey] = [];
        }
        if ($implName == '') {
            $implName = $interfaceName;
        }
        $this->relationMap[$relationKey][$implIndex] = [
            'type' => self::TYPE_NAME_MAP,
            'class' => $implName,
            'isSingleton' => $isSingleton
        ];
        if ($alias != '') {
            $this->aliasMap[$alias] = $interfaceName;
        }
        return $this;
    }

    public function addCallableMap(string $interfaceName, callable $callback, string $alias = '', bool $isSingleton = true, int $implIndex = 0)
    {
        $relationKey = $this->getRelationKey($interfaceName, $implIndex);
        if (! isset($this->relationMap[$relationKey])) {
            $this->relationMap[$relationKey] = [];
        }
        $this->relationMap[$relationKey][$implIndex] = [
            'type' => self::TYPE_CALLABLE_MAP,
            'callable' => $callback,
            'isSingleton' => $isSingleton
        ];
        if ($alias != '') {
            $this->aliasMap[$alias] = $interfaceName;
        }
        return $this;
    }

    public function addObjectMap(string $interfaceName, $object, string $alias = '', int $implIndex = 0)
    {
        $relationKey = $this->getRelationKey($interfaceName, $implIndex);
        if (! isset($this->relationMap[$relationKey])) {
            $this->relationMap[$relationKey] = [];
        }
        $this->relationMap[$relationKey][$implIndex] = [
            'type' => self::TYPE_OBJECT_MAP,
            'object' => $object,
            'isSingleton' => true
        ];
        if ($alias != '') {
            $this->aliasMap[$alias] = $interfaceName;
        }
        return $this;
    }

    private function makeObject(array $configData)
    {
        $object = null;
        switch ($configData['type']) {
            case self::TYPE_NAME_MAP:
                $classname = $configData['class'];
                $object = new $classname();
                break;
            case self::TYPE_CALLABLE_MAP:
                $callback = $configData['callable'];
                $object = call_user_func($callback);
                break;
            case self::TYPE_OBJECT_MAP:
                $object = $configData['object'];
                break;
        }
        return $object;
    }

    /**
     *
     * @param string $interfaceName            
     * @param int $implIndex            
     * @return object
     */
    public function make(string $interfaceName, int $implIndex = 0)
    {
        $relationKey = $this->getRelationKey($interfaceName, $implIndex);
        if (! isset($this->relationMap[$relationKey])) {
            throw new ContainerException('Injection of ' . $interfaceName . ' is not defined');
        }
        $configData = $this->relationMap[$relationKey][$implIndex];
        if ($configData['isSingleton']) {
            if (! isset($this->instanceMap[$relationKey])) {
                $object = $this->makeObject($configData);
                $this->instanceMap[$relationKey] = $object;
            }
            return $this->instanceMap[$relationKey];
        } else {
            return $this->makeObject($configData);
        }
    }

    /**
     *
     * @param string $interfaceName            
     * @param int $implIndex            
     * @return object
     */
    public function makeAlias(string $alias, int $implIndex = 0)
    {
        if (! isset($this->aliasMap[$alias])) {
            throw new ContainerException('alias: ' . $alias . ' is not defined');
        }
        return $this->make($this->aliasMap[$alias], $implIndex);
    }
}

