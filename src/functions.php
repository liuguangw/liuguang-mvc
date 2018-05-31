<?php

// 快捷函数
use liuguang\mvc\Application;
use liuguang\mvc\ObjectContainer;

/**
 * 获取应用单例对象
 *
 * @return Application
 */
function getApp(): Application
{
    return Application::$app;
}

/**
 * 获取容器对象
 *
 * @return ObjectContainer
 */
function getContainer(): ObjectContainer
{
    return Application::$app->container;
}

/**
 * 通过接口名获取注入的对象
 *
 * @param string $interfaceName            
 * @param int $implIndex            
 * @return object
 */
function makeObject(string $interfaceName, int $implIndex = 0)
{
    return Application::$app->container->make($interfaceName, $implIndex);
}

/**
 * 通过别名获取注入的对象
 *
 * @param string $alias
 *            别名
 * @param int $implIndex            
 * @return object
 */
function makeAliasObject(string $alias, int $implIndex = 0)
{
    return Application::$app->container->makeAlias($alias, $implIndex);
}

/**
 * 创建单例对象
 *
 * @param string $classname            
 * @param string $suffix            
 * @return object
 */
function createSingleton(string $classname, string $suffix = '')
{
    return Application::$app->container->createSingleton($classname, $suffix);
}