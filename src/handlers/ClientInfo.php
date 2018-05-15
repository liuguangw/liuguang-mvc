<?php
namespace liuguang\mvc\handlers;

use liuguang\mvc\Application;

/**
 * 客户端信息
 * 项目中应当根据部署情况(是否使用了cdn、反代等情况)重写部分方法
 *
 * @author liuguang
 *        
 */
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

