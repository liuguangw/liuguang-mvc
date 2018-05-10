<?php
namespace liuguang\mvc;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Debug\ExceptionHandler;
use liuguang\mvc\handlers\IErrorHandler;
use liuguang\mvc\handlers\IRouteHandler;
use liuguang\mvc\exceptions\ServerErrorHttpException;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Doctrine\DBAL\Connection;
use Symfony\Component\EventDispatcher\EventDispatcher;
define('MVC_SRC_PATH', __DIR__);

/**
 * 应用实例
 *
 * @author liuguang
 *        
 */
class Application
{

    const VERSION = '1.0';

    /**
     *
     * @var Application
     */
    public static $app = null;

    /**
     *
     * @var Request
     */
    public static $request = null;

    /**
     *
     * @var Response
     */
    public static $response = null;

    /**
     *
     * @var ObjectContainer
     */
    public $container;

    /**
     *
     * @var Controller
     */
    public $controller;

    /**
     *
     * @var Config
     */
    public $config;

    /**
     *
     * @var string
     */
    public $publicContext;

    /**
     *
     * @var array
     */
    private $controllers = [];

    /**
     *
     * @var EventDispatcher
     */
    private $eventDispatcher;

    const EVENT_BEFORE_RESPONSE = 'evt.before.response';

    const EVENT_AFTER_RESPONSE = 'evt.after.response';

    private function __construct(Config $config = null)
    {
        if (! defined('APP_PUBLIC_PATH')) {
            $handler = new ExceptionHandler();
            $handler->handle(new \Exception('constant APP_PUBLIC_PATH is not defined'));
            exit();
        }
        if (! defined('APP_SRC_PATH')) {
            define('APP_SRC_PATH', constant('APP_PUBLIC_PATH') . '/../src');
        }
        if (! defined('APP_CONFIG_DIR')) {
            define('APP_CONFIG_DIR', APP_SRC_PATH . '/./config');
        }
        if ($config === null) {
            $config = Config::loadFromPhpFile(APP_CONFIG_DIR . '/./config.inc.php');
        }
        $mvcConfig = Config::loadFromPhpFile(MVC_SRC_PATH . '/../config.inc.php');
        $this->config = $mvcConfig->merge($config);
        date_default_timezone_set($this->config->get('defaultTimeZone'));
        if (self::$request === null) {
            self::$request = Request::createFromGlobals();
        }
    }

    private function startApplication(bool $initTest): void
    {
        // ioc容器
        $this->container = new ObjectContainer();
        // 事件
        $this->eventDispatcher = new EventDispatcher();
        // 加载服务
        $serviceClass = $this->config->get('serviceClass');
        $service = new $serviceClass($this->container);
        try {
            $this->loadService($service);
            $this->setErrorHandler($this->container->makeAlias('errorHandler'));
        } catch (\Exception $e) {
            $handler = new ExceptionHandler();
            $handler->handle($e);
            return;
        }
        // context
        if (defined('APP_PUBLIC_CONTEXT')) {
            $this->publicContext = constant('APP_PUBLIC_CONTEXT');
        } elseif (PHP_SAPI == 'cli') {
            throw new \Exception('constant APP_PUBLIC_CONTEXT is not defined');
        } else {
            $context = '';
            $scriptName = self::$request->getScriptName();
            $pos = strrpos($scriptName, '/');
            if ($pos > 0) {
                $context = substr($scriptName, 0, $pos);
            }
            $this->publicContext = $context;
        }
        // 加载路由
        $this->setRouteHandler($this->container->makeAlias('routeHandler'));
        if (! $initTest) {
            $this->invokeRoute(Route::resolveRequest(self::$request));
        }
    }

    private function loadService(ServiceLoader $service): void
    {
        $service->loadService();
    }

    private function setErrorHandler(IErrorHandler $handler)
    {
        set_exception_handler(function ($err) use ($handler) {
            Application::$response = $handler->handle($err);
            Application::sendResponse();
            exit();
        });
        set_error_handler(function ($errno, $errstr, $errfile, $errline) {
            if (error_reporting() != 0) {
                throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
            }
        });
    }

    private function setRouteHandler(IRouteHandler $handler): void
    {
        $handler->load();
    }

    public function invokeRoute(Route $route): void
    {
        $moduleName = $route->getModuleName();
        $controllerId = $route->getControllerId();
        $actionId = $route->getActionId();
        self::$response = $response = $this->runAction($moduleName, $controllerId, $actionId);
        $controller = $this->controller;
        $controller->beforeResponse($controller->actionId, $response);
        $this->sendResponse();
        $controller->afterResponse($controller->actionId, $response);
    }

    /**
     * 项目入口
     *
     * @return void
     */
    public static function init(Config $config = null, bool $initTest = false): void
    {
        if (self::$app === null) {
            self::$app = new self($config);
            self::$app->startApplication($initTest);
        }
    }

    private function makeController(string $moduleName, string $controllerId, string $actionId): Controller
    {
        $uniqueId = $moduleName . '/' . $controllerId;
        if (! isset($this->controllers[$uniqueId])) {
            $controllerClass = $this->config->get('appControllerNs') . '\\' . str_replace('.', '\\', $moduleName) . '\\';
            if (strpos($controllerId, '-')) {
                $controllerName = ucwords(str_replace('-', ' ', $controllerId));
                $controllerName = str_replace(' ', '', $controllerId);
            } else {
                $controllerName = ucfirst($controllerId);
            }
            $controllerClass .= $controllerName;
            $suffix = $this->config->get('controllerClassSuffix', '');
            $controllerClass .= $suffix;
            if (! class_exists($controllerClass)) {
                throw new ServerErrorHttpException('控制器:' . $moduleName . '/' . $controllerId . '不存在');
            }
            $this->controllers[$uniqueId] = new $controllerClass($moduleName, $controllerId, $actionId);
        }
        return $this->controllers[$uniqueId];
    }

    public function runAction(string $moduleName, string $controllerId, string $actionId): Response
    {
        $this->controller = $this->makeController($moduleName, $controllerId, $actionId);
        return $this->controller->runAction($actionId);
    }

    /**
     * 解析actionId
     *
     * @param string $actionId            
     * @param array $emptyResult            
     * @return array [模块名,控制器名,操作名]
     * @throws \Exception
     */
    public function resolveActionId(string $actionStr, array $emptyResult = []): array
    {
        $actionStrArr = [];
        if (strpos($actionStr, '/')) {
            $actionStrArr = explode('/', $actionStr);
        } elseif ($actionStr != '') {
            $actionStrArr = [
                $actionStr
            ];
        }
        $resultArray = [];
        $rexpNames = [
            '操作ID',
            '控制器ID',
            '模块名'
        ];
        $moduleRexp = '#^([a-z_][a-z0-9_]*\.)*[a-z_][a-z0-9_]*$#';
        $actionRexp = '#^[a-z_][a-z0-9_]*(\-[a-z0-9_]*)*$#';
        $rexpArray = [
            $actionRexp,
            $actionRexp,
            $moduleRexp
        ];
        if (empty($emptyResult)) {
            $emptyResult = [
                $this->controller->moduleName,
                $this->controller->id,
                $this->controller->actionId
            ];
        }
        for ($i = 0; $i < 3; $i ++) {
            $tmpValue = array_pop($actionStrArr);
            if ($tmpValue === null) {
                $resultArray[] = $emptyResult[2 - $i];
            } else {
                if (preg_match($rexpArray[$i], $tmpValue) == 0) {
                    throw new \Exception($rexpNames[$i] . ':' . $tmpValue . '格式错误');
                }
                $resultArray[] = $tmpValue;
            }
        }
        return array_reverse($resultArray);
    }

    /**
     * 添加事件处理器
     *
     * @param string $eventName
     *            事件名
     * @param callable $listener
     *            处理器
     * @return void
     */
    public function addListener(string $eventName, callable $listener): void
    {
        $this->eventDispatcher->addListener($eventName, $listener);
    }

    public function sendResponse(): void
    {
        $this->eventDispatcher->dispatch(self::EVENT_BEFORE_RESPONSE);
        self::$response->prepare(self::$request);
        self::$response->send();
        $this->eventDispatcher->dispatch(self::EVENT_AFTER_RESPONSE);
    }

    /**
     * 获取session对象
     *
     * @return SessionInterface
     */
    public function getSession(): SessionInterface
    {
        return $this->container->makeAlias('session');
    }

    /**
     * 获取数据库连接对象
     *
     * @return Connection
     */
    public function getDb(?int $dbIndex = null): Connection
    {
        if ($dbIndex === null) {
            $dbIndex = Application::$app->config->get('appConn');
        }
        return $this->container->makeAlias('db', $dbIndex);
    }
}
