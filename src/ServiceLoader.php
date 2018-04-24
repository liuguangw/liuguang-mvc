<?php
namespace liuguang\mvc;

use liuguang\mvc\handlers\IErrorHandler;
use liuguang\mvc\handlers\DefaultErrorHandler;
use liuguang\mvc\handlers\IRouteHandler;
use liuguang\mvc\handlers\DefaultRouteHandler;

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
    }
}

