<?php
namespace liuguang\mvc\handlers;

use liuguang\mvc\Route;

class DefaultRouteHandler implements IRouteHandler
{

    /**
     *
     * {@inheritdoc}
     *
     * @see \liuguang\mvc\handlers\IRouteHandler::load()
     */
    public function load(): void
    {
        Route::get('/')->setName('index')->bind();
    }
}

