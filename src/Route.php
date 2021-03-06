<?php
namespace liuguang\mvc;

use Symfony\Component\HttpFoundation\Request;
use liuguang\mvc\exceptions\ServerErrorHttpException;
use liuguang\mvc\exceptions\NotFoundHttpException;

class Route
{

    const TYPE_EQUAL = 0;

    const TYPE_MATCH = 1;

    /**
     * 根据配置文件决定生成的URL是完整的还是缩略的
     *
     * @var integer
     */
    const URL_DIST_CONFIG = 0;

    /**
     * 生成缩略URL
     *
     * @var integer
     */
    const URL_DIST_SHORT = 1;

    /**
     * 生成完整URL
     *
     * @var integer
     */
    const URL_DIST_LONG = 2;

    protected static $rules = [
        'get' => [],
        'post' => [],
        'put' => [],
        'patch' => [],
        'delete' => [],
        'options' => []
    ];

    protected static $nameMap = [];

    protected $url;

    protected $parseCallback;

    protected $creatorCallback;

    protected $resolveCallback;

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

    /**
     * 为路由设置别名
     *
     * @param string $name            
     * @return Route
     */
    public function setName(string $name): Route
    {
        $this->name = $name;
        return $this;
    }

    /**
     * 获取路由别名
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * 设置路由回调方法(用于正则匹配url)
     *
     * @param callable $parseCallback
     *            路由参数解析回调
     * @param callable $creatorCallback
     *            路由url生成回调
     * @return $this
     */
    public function setCallback(callable $parseCallback, callable $creatorCallback): Route
    {
        $this->parseCallback = $parseCallback;
        $this->creatorCallback = $creatorCallback;
        $this->urlType = self::TYPE_MATCH;
        return $this;
    }

    /**
     * 解析路由时,设置请求相关信息
     *
     * @param Request $request            
     * @param string $path            
     * @return voids
     */
    public function setRequestInfo(Request $request, string $path): void
    {
        if ($this->resolveCallback !== null) {
            list ($this->moduleName, $this->controllerId, $this->actionId) = call_user_func($this->resolveCallback, $request, $path);
        }
    }

    /**
     * 获取路由信息
     *
     * @return array
     */
    public function getRouteInfo(): array
    {
        return [
            $this->moduleName,
            $this->controllerId,
            $this->actionId
        ];
    }

    /**
     * 将路由绑定到控制器的操作
     *
     * @param string $actionStr
     *            操作
     * @return void
     */
    public function bind(string $actionStr = ''): void
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
        if ($this->name == '') {
            $this->name = '@' . $moduleName . '/' . $controllerId . '/' . $actionId;
        }
        self::$nameMap[$this->name] = $this;
        foreach ($this->requestMethods as $method) {
            self::$rules[$method][$this->urlType][$this->url] = $this;
        }
    }

    /**
     * 绑定路由到控制器的操作中(操作需要调用此回调解析)
     *
     * @param callable $resolveCallback            
     * @return void
     */
    public function bindResolveCallback(callable $resolveCallback): void
    {
        $this->resolveCallback = $resolveCallback;
        foreach ($this->requestMethods as $method) {
            self::$rules[$method][$this->urlType][$this->url] = $this;
        }
    }

    /**
     * 获取路由标识(url或者正则表达式)
     *
     * @return string
     */
    public function getIdentity(): string
    {
        return $this->url;
    }

    /**
     * 获取正则匹配中得到的参数数组
     *
     * @param array $matchData            
     * @return array
     */
    public function parseActionParams(array $matchData): array
    {
        return call_user_func($this->parseCallback, $matchData);
    }

    /**
     * 将请求解析为路由信息
     *
     * @param Request $request            
     * @throws ServerErrorHttpException
     * @throws NotFoundHttpException
     * @return Route
     */
    public static function resolveRequest(Request $request): Route
    {
        // 过滤context
        $requestUri = $request->getRequestUri();
        if (Application::$app->publicContext != '') {
            $requestUri = substr($requestUri, strlen(Application::$app->publicContext));
        }
        // 解析url的path和参数部分
        $uriInfo = parse_url($requestUri);
        $path = $uriInfo['path'];
        if (isset($uriInfo['query'])) {
            $params = [];
            parse_str($uriInfo['query'], $params);
            // 向request中写入GET参数
            $request->query->add($params);
        }
        // 获取请求方式(GET、POST...)
        $requestMethod = strtolower($request->getMethod());
        if (! isset(self::$rules[$requestMethod])) {
            throw new ServerErrorHttpException('known request method ' . $requestMethod);
        }
        // 获取对应请求方式的所以路由规则(完全匹配规则)
        $rules = self::$rules[$requestMethod];
        if (isset($rules[self::TYPE_EQUAL])) {
            $equalCollection = $rules[self::TYPE_EQUAL];
            foreach ($equalCollection as $routeInfo) {
                // 如果找到完全匹配地址,则返回路由对象
                if ($routeInfo->getIdentity() == $path) {
                    $routeInfo->setRequestInfo($request, $path);
                    return $routeInfo;
                }
            }
        }
        // 无完全匹配条目、进行正则匹配
        if (isset($rules[self::TYPE_MATCH])) {
            $matchCollection = $rules[self::TYPE_MATCH];
            foreach ($matchCollection as $routeInfo) {
                $identity = $routeInfo->getIdentity();
                if (preg_match($identity, $path, $matchData) != 0) {
                    // 正则匹配的URL中的额外参数加入request对象
                    $matchParams = $routeInfo->parseActionParams($matchData);
                    $request->query->add($matchParams);
                    $routeInfo->setRequestInfo($request, $path);
                    return $routeInfo;
                }
            }
        }
        // 没有匹配到任何路由
        throw new NotFoundHttpException('访问的页面不存在');
    }

    /**
     * 构建URL
     *
     * @param string $url
     *            原URL
     * @param int $distUrlType
     *            目标URL类型
     * @param array $options
     *            [string host,string scheme]
     * @return string
     */
    private static function buildUrl(string $url, int $distUrlType = 0, array $options = []): string
    {
        $url = Application::$app->publicContext . $url;
        if (! in_array($distUrlType, [
            self::URL_DIST_CONFIG,
            self::URL_DIST_SHORT,
            self::URL_DIST_LONG
        ])) {
            $distUrlType = self::URL_DIST_CONFIG;
        }
        $config = Application::$app->config;
        if ($distUrlType == self::URL_DIST_CONFIG) {
            $distUrlType = $config->get('app_url_type');
        }
        if ($distUrlType == self::URL_DIST_LONG) {
            if (isset($options['scheme'])) {
                $scheme = $options['scheme'];
            } elseif ($config->has('app_scheme')) {
                $scheme = $config->get('app_scheme');
            } else {
                $scheme = Application::$request->getScheme();
            }
            if (isset($options['host'])) {
                $host = $options['host'];
            } elseif ($config->has('app_host')) {
                $host = $config->get('app_host');
            } else {
                $host = Application::$request->getHttpHost();
            }
            $url = $scheme . '://' . $host . $url;
        }
        return $url;
    }

    /**
     * 生成目标url
     *
     * @param array $actionParams
     *            附加url参数
     * @return string
     */
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

    /**
     * 根据action生成url
     *
     * @param string $actionStr            
     * @param array $params            
     * @param int $distUrlType            
     * @param array $options            
     * @return string
     */
    public static function createUrl(string $actionStr, array $params = [], int $distUrlType = 0, array $options = []): string
    {
        list ($moduleName, $controllerId, $actionId) = Application::$app->resolveActionId($actionStr);
        return self::createUrlByName('@' . $moduleName . '/' . $controllerId . '/' . $actionId, $params, $distUrlType, $options);
    }

    /**
     * 根据路由名称生成url
     *
     * @param string $routeName            
     * @param array $params            
     * @param int $distUrlType            
     * @param array $options            
     * @return string
     */
    public static function createUrlByName(string $routeName, array $params = [], int $distUrlType = 0, array $options = []): string
    {
        $route = self::findRouteByName($routeName);
        return self::createUrlByRoute($route, $params, $distUrlType, $options);
    }

    /**
     * 根据路由对象生成url
     *
     * @param Route $route            
     * @param array $params            
     * @param int $distUrlType            
     * @param array $options            
     * @return string
     */
    public static function createUrlByRoute(Route $route, array $params = [], int $distUrlType = 0, array $options = []): string
    {
        $url = $route->makeDistUrl($params);
        return self::buildUrl($url, $distUrlType, $options);
    }

    /**
     * 根据路由名称查找路由
     *
     * @param string $routeName            
     * @throws ServerErrorHttpException
     * @return Route
     */
    public static function findRouteByName(string $routeName): Route
    {
        if (isset(self::$nameMap[$routeName])) {
            return self::$nameMap[$routeName];
        }
        throw new ServerErrorHttpException('找不到名称为' . $routeName . '的路由');
    }
}

