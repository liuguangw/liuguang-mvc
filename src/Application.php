<?php
namespace liuguang\mvc;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use liuguang\mvc\handlers\IErrorHandler;
use liuguang\mvc\handlers\IRouteHandler;
use liuguang\mvc\exceptions\ServerErrorHttpException;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Doctrine\DBAL\Connection;
use Symfony\Component\EventDispatcher\EventDispatcher;
use liuguang\mvc\handlers\DefaultErrorHandler;
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
     * 项目app单例
     *
     * @var Application
     */
    public static $app = null;

    /**
     * 请求对象
     *
     * @var Request
     */
    public static $request = null;

    /**
     * 响应对象(控制器的操作返回被调用后才有)
     *
     * @var Response
     */
    public static $response = null;

    /**
     * 对象容器
     *
     * @var ObjectContainer
     */
    public $container;

    /**
     * 当前控制器对象
     *
     * @var Controller
     */
    public $controller;

    /**
     * 配置对象
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
     * 事件分发工具
     *
     * @var EventDispatcher
     */
    private $eventDispatcher;

    const EVENT_BEFORE_RESPONSE = 'evt.before.response';

    const EVENT_AFTER_RESPONSE = 'evt.after.response';

    /**
     *
     * @param Config $config
     *            配置对象
     */
    private function __construct(Config $config = null)
    {
        if (! defined('APP_PUBLIC_PATH')) {
            $this->traceError(new \ErrorException('constant APP_PUBLIC_PATH is not defined'));
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

    /**
     * 启动项目
     *
     * @param bool $initTest
     *            是否启动测试
     * @throws \Exception
     * @return void
     */
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
            $this->traceError($e);
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

    /**
     * 尚未注册错误处理器时,提示错误信息
     *
     * @param \Throwable $err            
     * @return void
     */
    private function traceError(\Throwable $err): void
    {
        $handler = new DefaultErrorHandler();
        self::$response = $handler->handle($err);
        $this->sendResponse();
    }

    /**
     * 加载服务绑定
     *
     * @param ServiceLoader $service            
     * @return void
     */
    private function loadService(ServiceLoader $service): void
    {
        $service->loadService();
    }

    /**
     * 设置错误处理handler
     *
     * @param IErrorHandler $handler            
     * @throws \ErrorException
     * @return void
     */
    private function setErrorHandler(IErrorHandler $handler): void
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

    /**
     * 加载路由配置
     *
     * @param IRouteHandler $handler            
     * @return void
     */
    private function setRouteHandler(IRouteHandler $handler): void
    {
        $handler->load();
    }

    /**
     * 根据路由对象,执行对应的控制器的方法
     *
     * @param Route $route
     *            解析得到的路由对象
     * @return void
     */
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
     * @param Config $config
     *            配置对象
     * @param bool $initTest
     *            是否为测试,默认为false(测试将不执行控制器)
     * @return void
     */
    public static function init(Config $config = null, bool $initTest = false): void
    {
        if (self::$app === null) {
            self::$app = new self($config);
            self::$app->startApplication($initTest);
        }
    }

    /**
     * 控制器单例工厂
     *
     * @param string $moduleName            
     * @param string $controllerId            
     * @param string $actionId            
     * @throws ServerErrorHttpException
     * @return Controller
     */
    private function makeController(string $moduleName, string $controllerId, string $actionId): Controller
    {
        $uniqueId = $moduleName . '/' . $controllerId;
        $suffix = '#controller';
        if (! $this->container->hasBindSingleton($uniqueId, $suffix)) {
            $controllerClass = $this->config->get('appControllerNs') . '\\' . str_replace('.', '\\', $moduleName) . '\\';
            // abc-def-ghi=>AbcDefGhi
            if (strpos($controllerId, '-')) {
                $controllerName = ucwords(str_replace('-', ' ', $controllerId));
                $controllerName = str_replace(' ', '', $controllerId);
            } else {
                $controllerName = ucfirst($controllerId);
            }
            $controllerClass .= $controllerName;
            $controllerClass .= $this->config->get('controllerClassSuffix', '');
            if (! class_exists($controllerClass)) {
                throw new ServerErrorHttpException('控制器:' . $moduleName . '/' . $controllerId . '对应的类[' . $controllerClass . ']不存在');
            }
            $controllerObj = new $controllerClass($moduleName, $controllerId, $actionId);
            $this->container->bindSingleton($controllerObj, $uniqueId, $suffix);
            return $controllerObj;
        }
        return $this->container->createSingleton($uniqueId, $suffix);
    }

    /**
     * 调用操作
     *
     * @param string $moduleName
     *            模块名
     * @param string $controllerId
     *            控制器名
     * @param string $actionId
     *            操作名
     * @return Response
     */
    public function runAction(string $moduleName, string $controllerId, string $actionId): Response
    {
        $this->controller = $this->makeController($moduleName, $controllerId, $actionId);
        return $this->controller->runAction($actionId);
    }

    /**
     * 解析actionId
     *
     * @param string $actionStr            
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
        $moduleRexp = '#^[a-z_][a-z0-9_]*(\.[a-z_][a-z0-9_]*)*$#';
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

    /**
     * 发送响应
     *
     * @return void
     */
    public function sendResponse(): void
    {
        if ($this->eventDispatcher !== null) {
            $this->eventDispatcher->dispatch(self::EVENT_BEFORE_RESPONSE);
        }
        if (self::$request !== null) {
            self::$response->prepare(self::$request);
        }
        self::$response->send();
        if ($this->eventDispatcher !== null) {
            $this->eventDispatcher->dispatch(self::EVENT_AFTER_RESPONSE);
        }
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
            $dbIndex = $this->config->get('appConn');
        }
        return $this->container->makeAlias('db', $dbIndex);
    }
}
