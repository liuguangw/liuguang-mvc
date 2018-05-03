<?php
namespace liuguang\mvc\handlers;

use liuguang\mvc\Application;

class ClientInfo
{

    /**
     * ip
     *
     * @return string
     */
    public function getIp(): string
    {
        return Application::$request->server->get('REMOTE_ADDR', '');
    }

    /**
     * useragent
     *
     * @return string
     */
    public function getUserAgent(): string
    {
        return Application::$request->headers->get('User-Agent', '');
    }

    /**
     * 获取浏览器或者客户端名称
     *
     * @return string
     */
    public function getBrowser(): string
    {
        throw new \Exception('not support');
    }
}

