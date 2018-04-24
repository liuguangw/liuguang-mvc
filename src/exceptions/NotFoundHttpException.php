<?php
namespace liuguang\mvc\exceptions;

class NotFoundHttpException extends HttpException
{

    public function __construct(string $message, int $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, 404, $code, $previous);
    }
}

