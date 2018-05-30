<?php
namespace liuguang\mvc;

use liuguang\mvc\exceptions\ContainerException;

/**
 * ioc容器和单例容器
 *
 * @author liuguang
 *        
 */
class ObjectContainer
{

    const TYPE_NAME_MAP = 0;

    const TYPE_CALLABLE_MAP = 1;

    const TYPE_OBJECT_MAP = 2;

    private $relationMap;

    private $aliasMap;

    private $singletonMap;

    public function __construct()
    {
        $this->relationMap = [];
        $this->aliasMap = [];
        $this->singletonMap = [];
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

    public function hasBindRelation(string $interfaceName, int $implIndex = 0): bool
    {
        $relationKey = $this->getRelationKey($interfaceName, $implIndex);
        return isset($this->relationMap[$relationKey]);
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
            $suffix = '#' . $implIndex;
            if (! $this->hasBindSingleton($interfaceName, $suffix)) {
                $this->bindSingleton($this->makeObject($configData), $interfaceName, $suffix);
            }
            return $this->createSingleton($interfaceName, $suffix);
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

    /**
     * 判断是否绑定了单例对象
     *
     * @param string $classname            
     * @param string $suffix            
     * @return bool
     */
    public function hasBindSingleton(string $classname, string $suffix = ''): bool
    {
        return isset($this->singletonMap[$classname . $suffix]);
    }

    /**
     * 绑定单例对象
     *
     * @param object $obj            
     * @param string $classname            
     * @param string $suffix            
     * @return void
     */
    public function bindSingleton($obj, string $classname, string $suffix = ''): void
    {
        $this->singletonMap[$classname . $suffix] = $obj;
    }

    /**
     * 创建单例对象
     *
     * @param string $classname            
     * @param string $suffix            
     * @return object
     */
    public function createSingleton(string $classname, string $suffix = '')
    {
        if (! $this->hasBindSingleton($classname, $suffix)) {
            $this->bindSingleton(new $classname(), $classname, $suffix);
        }
        return $this->singletonMap[$classname . $suffix];
    }
}

