<?php
namespace liuguang\mvc;

use Symfony\Component\HttpFoundation\Request;
use liuguang\mvc\exceptions\ServerErrorHttpException;
use liuguang\mvc\exceptions\NotFoundHttpException;

class Route
{

    const TYPE_EQUAL = 0;

    const TYPE_MATCH = 1;

    protected static $rules = [
        'get' => [],
        'post' => [],
        'put' => [],
        'patch' => [],
        'delete' => [],
        'options' => []
    ];

    protected static $nameMap = [];

    protected static $actionMap = [];

    protected $url;

    protected $parseCallback;

    protected $creatorCallback;

    protected $urlType = 0;

    protected $requestMethods;

    protected $name = '';

    protected $moduleName;

    protected $controllerId;

    protected $actionId;

    public static function get(string $url): Route
    {
        return static::request($url, [
            'get'
        ]);
    }

    public static function post(string $url): Route
    {
        return static::request($url, [
            'post'
        ]);
    }

    public static function put(string $url): Route
    {
        return static::request($url, [
            'put'
        ]);
    }

    public static function patch(string $url): Route
    {
        return static::request($url, [
            'patch'
        ]);
    }

    public static function delete(string $url): Route
    {
        return static::request($url, [
            'delete'
        ]);
    }

    public static function options(string $url): Route
    {
        return static::request($url, [
            'options'
        ]);
    }

    public static function request(string $url, array $requestMethods = []): Route
    {
        if (empty($requestMethods)) {
            $requestMethods = [
                'get',
                'post',
                'put',
                'patch',
                'delete',
                'options'
            ];
        }
        return new static($url, $requestMethods);
    }

    public function __construct(string $url, array $requestMethods)
    {
        $this->url = $url;
        $this->requestMethods = $requestMethods;
    }

    public function setName(string $name)
    {
        $this->name = $name;
        return $this;
    }

    public function getName(string $name)
    {
        return $this->name;
    }

    public function setCallback(callable $parseCallback, callable $creatorCallback)
    {
        $this->parseCallback = $parseCallback;
        $this->creatorCallback = $creatorCallback;
        $this->urlType = self::TYPE_MATCH;
        return $this;
    }

    public function bind(string $actionStr = '')
    {
        $config = Application::$app->config;
        $emptyResult = [
            $config->get('defaultModule'),
            $config->get('defaultController'),
            $config->get('defaultAction')
        ];
        list ($moduleName, $controllerId, $actionId) = Application::$app->resolveActionId($actionStr, $emptyResult);
        $this->moduleName = $moduleName;
        $this->controllerId = $controllerId;
        $this->actionId = $actionId;
        if ($this->name != '') {
            self::$nameMap[$this->name] = $this;
        }
        self::$actionMap[$moduleName . '/' . $controllerId . '/' . $actionId] = $this;
        foreach ($this->requestMethods as $method) {
            self::$rules[$method][$this->urlType][$this->url] = $this;
        }
    }

    /**
     *
     * @return the $moduleName
     */
    public function getModuleName()
    {
        return $this->moduleName;
    }

    /**
     *
     * @return the $controllerId
     */
    public function getControllerId()
    {
        return $this->controllerId;
    }

    /**
     *
     * @return the $actionId
     */
    public function getActionId()
    {
        return $this->actionId;
    }

    public function getIdentity()
    {
        return $this->url;
    }

    public function parseActionParams(array $matchData): array
    {
        return call_user_func($this->parseCallback, $matchData);
    }

    public static function resolveRequest(Request $request): Route
    {
        $requestUri = $request->getRequestUri();
        if (Application::$app->publicContext != '') {
            $requestUri = substr($requestUri, strlen(Application::$app->publicContext));
        }
        $uriInfo = parse_url($requestUri);
        $path = $uriInfo['path'];
        if (isset($uriInfo['query'])) {
            $params = [];
            parse_str($uriInfo['query'], $params);
            $request->query->add($params);
        }
        $requestMethod = strtolower($request->getMethod());
        if (! isset(self::$rules[$requestMethod])) {
            throw new ServerErrorHttpException('known request method ' . $requestMethod);
        }
        $rules = self::$rules[$requestMethod];
        if (isset($rules[self::TYPE_EQUAL])) {
            $equalCollection = $rules[self::TYPE_EQUAL];
            foreach ($equalCollection as $routeInfo) {
                if ($routeInfo->getIdentity() == $path) {
                    return $routeInfo;
                }
            }
        }
        if (isset($rules[self::TYPE_MATCH])) {
            $matchCollection = $rules[self::TYPE_MATCH];
            foreach ($matchCollection as $routeInfo) {
                $identity = $routeInfo->getIdentity();
                if (preg_match($identity, $path, $matchData) != 0) {
                    $matchParams = $routeInfo->parseActionParams($matchData);
                    $request->query->add($matchParams);
                    return $routeInfo;
                }
            }
        }
        throw new NotFoundHttpException('访问的页面不存在');
    }

    private static function buildUrl(string $url, bool $fullUrl = false, array $options = [])
    {
        $url = Application::$app->publicContext . $url;
        if ($fullUrl) {
            $request = Application::$request;
            $config = Application::$app->config;
            if (isset($options['scheme'])) {
                $scheme = $options['scheme'];
            } else {
                $scheme = $config->get('app_scheme', $request->getScheme());
            }
            if (isset($options['host'])) {
                $host = $options['host'];
            } else {
                $host = $config->get('app_host', $request->getHttpHost());
            }
            $url = $scheme . '://' . $host . $url;
        }
        return $url;
    }

    public function makeDistUrl(array $actionParams = []): string
    {
        if ($this->urlType == self::TYPE_EQUAL) {
            $url = $this->url;
        } else {
            list ($url, $actionParams) = call_user_func($this->creatorCallback, $actionParams);
        }
        if (! empty($actionParams)) {
            $url .= ('?' . http_build_query($actionParams));
        }
        return $url;
    }

    public static function createUrl(string $actionStr, array $params = [], bool $fullUrl = false, array $options = []): string
    {
        $route = self::findRoute($actionStr);
        return self::createUrlByRoute($route, $params, $fullUrl, $options);
    }

    public static function createUrlByName(string $routeName, array $params = [], bool $fullUrl = false, array $options = []): string
    {
        $route = self::findRouteByName($routeName);
        return self::createUrlByRoute($route, $params, $fullUrl, $options);
    }

    public static function createUrlByRoute(Route $route, array $params = [], bool $fullUrl = false, array $options = []): string
    {
        $url = $route->makeDistUrl($params);
        return self::buildUrl($url, $fullUrl, $options);
    }

    public static function findRoute(string $actionStr): Route
    {
        list ($moduleName, $controllerId, $actionId) = Application::$app->resolveActionId($actionStr);
        $uniqueId = $moduleName . '/' . $controllerId . '/' . $actionId;
        if (isset(self::$actionMap[$uniqueId])) {
            return self::$actionMap[$uniqueId];
        }
        throw new ServerErrorHttpException('找不到' . $uniqueId . '对应的路由');
    }

    public static function findRouteByName(string $routeName): Route
    {
        if (isset(self::$nameMap[$routeName])) {
            return self::$nameMap[$routeName];
        }
        throw new ServerErrorHttpException('找不到名称为' . $routeName . '的路由');
    }
}

