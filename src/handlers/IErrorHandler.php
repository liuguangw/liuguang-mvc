<?php
namespace liuguang\mvc\handlers;

use Symfony\Component\HttpFoundation\Response;

interface IErrorHandler
{
    /**
     * 处理异常/错误
     * 
     * @param \Throwable $err
     * @return Response
     */
    public function handle(\Throwable $err): Response;
}

