<?php
namespace liuguang\mvc\handlers;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Debug\ExceptionHandler;
use liuguang\mvc\exceptions\HttpException;
use Symfony\Component\Debug\Exception\FlattenException;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
        if (PHP_SAPI == 'cli') {
            return $this->cliHandle($err);
        }
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

    private function cliHandle(\Throwable $err): StreamedResponse
    {
        return new StreamedResponse(function () use (&$err) {
            $errList = [
                $err
            ];
            while ($err->getPrevious() !== null) {
                $err = $err->getPrevious();
                $errList[] = $err;
            }
            $maxDeep = 4;
            $errCount = count($errList);
            $eIndex = 0;
            foreach ($errList as $err) {
                $eIndex ++;
                echo '(', $eIndex, '/', $errCount, ')', get_class($err), PHP_EOL;
                echo $err->getMessage(), PHP_EOL;
                echo 'in ', $err->getFile(), ' line ', $err->getLine(), PHP_EOL;
                $traceArr = $err->getTrace();
                $traceIndex = 0;
                foreach ($traceArr as $traceInfo) {
                    if ($traceIndex >= $maxDeep) {
                        break;
                    }
                    $file = '<unknown file>';
                    if (isset($traceInfo['file'])) {
                        $file = $traceInfo['file'];
                    }
                    $line = '<unknown line>';
                    if (isset($traceInfo['line'])) {
                        $line = $traceInfo['line'];
                    }
                    $func = '<unknown func>';
                    if (isset($traceInfo['function'])) {
                        $func = $traceInfo['function'];
                    }
                    if (isset($traceInfo['class'])) {
                        $func = $traceInfo['class'] . $traceInfo['type'];
                    }
                    $func .= '()';
                    echo '#', $traceIndex, ' ', $file, '(', $line, '): ', $func, PHP_EOL;
                    $traceIndex ++;
                }
                echo PHP_EOL;
            }
        });
    }
}

