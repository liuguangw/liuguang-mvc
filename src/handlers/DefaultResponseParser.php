<?php
namespace liuguang\mvc\handlers;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

class DefaultResponseParser implements IResponseParser
{

    /**
     *
     * {@inheritdoc}
     *
     * @see \liuguang\mvc\handlers\IResponseParser::parseResponse()
     */
    public function parseResponse($content): Response
    {
        if (is_string($content)) {
            return new Response($content);
        } elseif (is_array($content)) {
            return new JsonResponse($content);
        } else {
            throw new \Exception('unsupport response data type');
        }
    }
}

