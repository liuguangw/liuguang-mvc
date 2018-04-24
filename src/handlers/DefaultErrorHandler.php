<?php
namespace liuguang\mvc\handlers;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Debug\ExceptionHandler;
use liuguang\mvc\exceptions\HttpException;
use Symfony\Component\Debug\Exception\FlattenException;

class DefaultErrorHandler implements IErrorHandler
{

    /**
     *
     * @var ExceptionHandler
     */
    private $handler;

    public function __construct()
    {
        $this->handler = new ExceptionHandler();
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \liuguang\mvc\handlers\IErrorHandler::handle()
     */
    public function handle(\Throwable $err): Response
    {
        $httpCode = 500;
        if ($err instanceof \Exception) {
            $exception = $err;
            if ($err instanceof HttpException) {
                $httpCode = $err->httpCode;
            }
        } else {
            $exception = new \Exception($err->getMessage(), $err->getCode(), $err);
        }
        $exception = FlattenException::create($exception, $httpCode);
        return new Response($this->handler->getHtml($exception), $httpCode);
    }
}

