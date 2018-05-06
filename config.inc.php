<?php
return [
    'serviceClass' => 'liuguang\mvc\ServiceLoader',
    'appControllerNs' => 'app\controllers',
    'controllerClassSuffix' => 'Controller',
    'defaultModule' => 'home',
    'defaultController' => 'index',
    'defaultAction' => 'index',
    'templateDir' => constant('APP_SRC_PATH') . '/./template',
    'forceRebuildTemplate' => true,
    'defaultContentType' => 'text/html; charset=UTF-8',
    'defaultTimeZone' => 'Asia/Shanghai',
    'staticUrlVersion' => 'v1',
    'app_url_type' => 1,
    'dbConfigList' => [],
    // 默认连接的编号
    'appConn' => 0,
    'migrationDir' => constant('APP_SRC_PATH') . '/./migrations',
    'migrationTable' => 'migrations',
    'migrationNs' => 'app\migrations'
    // 'migrationLogerConn' => 0
];