<?php
namespace liuguang\mvc\handlers;

use liuguang\mvc\exceptions\HttpException;
use Whoops\Run;
use Symfony\Component\HttpFoundation\Response;
use Whoops\Util\Misc;
use Whoops\Handler\PlainTextHandler;
use Whoops\Handler\PrettyPageHandler;

class DefaultErrorHandler implements IErrorHandler
{

    /**
     *
     * @var Run
     */
    private $handler;

    public function __construct()
    {
        $this->handler = new Run();
        $this->handler->writeToOutput(false);
        $this->handler->allowQuit(false);
        if (Misc::isCommandLine()) {
            $this->handler->pushHandler(new PlainTextHandler());
        } else {
            $this->handler->pushHandler(new PrettyPageHandler());
        }
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
        //var_dump($this->handler->handleException($err));exit();
        return new Response($this->handler->handleException($err), $httpCode);
    }
}

