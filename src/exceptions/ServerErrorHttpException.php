<?php
namespace liuguang\mvc\exceptions;

class ServerErrorHttpException extends HttpException
{
    
    public function __construct(string $message, int $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, 500, $code, $previous);
    }
}
