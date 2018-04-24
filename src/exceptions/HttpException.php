<?php
namespace liuguang\mvc\exceptions;

class HttpException extends \Exception
{

    public $httpCode;

    public function __construct(string $message, int $httpCode = 500, int $code = 0, \Throwable $previous = null)
    {
        $this->httpCode = $httpCode;
        parent::__construct($message, $code, $previous);
    }
}

