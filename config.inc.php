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
    'dbConfigList' => []
];