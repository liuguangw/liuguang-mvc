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
    }
}

