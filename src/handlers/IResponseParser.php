<?php
namespace liuguang\mvc\handlers;

use Symfony\Component\HttpFoundation\Response;

interface IResponseParser
{

    /**
     * 将非Response类型解析为Response
     *
     * @param mixed $content
     *            返回内容
     * @return Response
     * @throws \Exception
     */
    public function parseResponse($content): Response;
}

