<?php
namespace liuguang\mvc;

use liuguang\mvc\handlers\IErrorHandler;
use liuguang\mvc\handlers\DefaultErrorHandler;
use liuguang\mvc\handlers\IRouteHandler;
use liuguang\mvc\handlers\DefaultRouteHandler;
use liuguang\mvc\handlers\ITemplate;
use liuguang\mvc\handlers\DefaultTemplate;
use liuguang\mvc\handlers\UrlAsset;
use liuguang\mvc\handlers\DefaultUrlAsset;
use liuguang\mvc\handlers\ClientInfo;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\NativeFileSessionHandler;
use Doctrine\DBAL\Connection;

class ServiceLoader
{

    /**
     *
     * @var ObjectContainer
     */
    public $container;

    public function __construct(ObjectContainer $container)
    {
        $this->container = $container;
    }

    public function loadService()
    {
        $this->container->addNameMap(IErrorHandler::class, DefaultErrorHandler::class, 'errorHandler');
        $this->container->addNameMap(IRouteHandler::class, DefaultRouteHandler::class, 'routeHandler');
        $this->container->addNameMap(UrlAsset::class, DefaultUrlAsset::class, 'urlAsset');
        $this->container->addNameMap(ITemplate::class, DefaultTemplate::class, 'template', false);
        $this->container->addNameMap(ClientInfo::class, '', 'clientInfo');
        // session
        $this->container->addCallableMap(SessionInterface::class, function () {
            if (! Application::$request->hasSession()) {
                $sessionStorage = new NativeSessionStorage(array(), new NativeFileSessionHandler());
                $session = new Session($sessionStorage);
                Application::$request->setSession($session);
            }
            return Application::$request->getSession();
        }, 'session');
        // 注册数据库连接
        $dbConfigList = Application::$app->config->get('dbConfigList', []);
        foreach ($dbConfigList as $dbIndex => $dbConfig) {
            $this->container->addCallableMap(Connection::class, function () use ($dbConfig) {
                $config = new \Doctrine\DBAL\Configuration();
                return \Doctrine\DBAL\DriverManager::getConnection($dbConfig, $config);
            }, 'db', true, $dbIndex);
        }
    }
}

