<?php
namespace liuguang\mvc;

use Symfony\Component\HttpFoundation\Response;
use liuguang\mvc\exceptions\ServerErrorHttpException;
use liuguang\mvc\handlers\ITemplate;

class Controller
{

    /**
     * 模块名
     *
     * @var string
     */
    public $moduleName;

    /**
     * 控制器名
     *
     * @var string
     */
    public $id;

    /**
     *
     * @var string
     */
    public $actionId;

    public function __construct(string $moduleName, string $id, string $actionId)
    {
        $this->moduleName = $moduleName;
        $this->id = $id;
        $this->actionId = $actionId;
        Application::$app->controller = $this;
    }

    public function getUniqueActionId(): string
    {
        return $this->moduleName . '/' . $this->id . '/' . $this->actionId;
    }

    protected function getActionMethodName(string $actionId): string
    {
        if (strpos($actionId, '-')) {
            $actionId = ucwords(str_replace('-', ' ', $actionId));
            $actionId = str_replace(' ', '', $actionId);
        } else {
            $actionId = ucfirst($actionId);
        }
        return 'action' . $actionId;
    }

    /**
     * 执行操作
     *
     * @param string $actionId
     *            操作标识
     * @return Response
     */
    public function runAction(string $actionId): Response
    {
        if ($this->actionId != $actionId) {
            $this->actionId = $actionId;
        }
        $response = null;
        $actionMethodName = $this->getActionMethodName($actionId);
        try {
            $methodInfo = new \ReflectionMethod($this, $actionMethodName);
            if ($methodInfo->isPublic()) {
                $response = $this->invokeAction($methodInfo);
                // 若操作返回非response对象,则交由解析器处理
                if (! ($response instanceof Response)) {
                    /**
                     *
                     * @var \liuguang\mvc\handlers\IResponseParser $parser
                     */
                    $parser = Application::$app->container->makeAlias('responseParser');
                    $response = $parser->parseResponse($response);
                }
            } else {
                throw new ServerErrorHttpException('操作:' . $this->getUniqueActionId() . '不存在');
            }
        } catch (\ReflectionException $e) {
            throw new ServerErrorHttpException('操作:' . $this->getUniqueActionId() . '不存在');
        }
        return $response;
    }

    private function invokeAction(\ReflectionMethod $methodInfo)
    {
        // 获取参数
        if ($methodInfo->getNumberOfParameters() == 0) {
            return $methodInfo->invoke($this);
        }
        // 控制器参数注入
        $paramsArr = $methodInfo->getParameters();
        $args = [];
        $container = Application::$app->container;
        foreach ($paramsArr as $paramInfo) {
            /**
             *
             * @var \ReflectionClass $interfaceInfo
             */
            $interfaceInfo = $paramInfo->getClass();
            if (empty($interfaceInfo)) {
                throw new ServerErrorHttpException('操作方法的参数缺少接口声明');
            }
            $interfaceName = $interfaceInfo->getName();
            if ($container->hasBindRelation($interfaceName)) {
                $args[] = $container->make($interfaceName);
            } else {
                $args[] = $container->createSingleton($interfaceName);
            }
        }
        return $methodInfo->invokeArgs($this, $args);
    }

    /**
     *
     * @param string $actionId            
     * @return void
     */
    public function beforeResponse(string $actionId, Response $response): void
    {}

    /**
     *
     * @param string $actionId            
     * @return void
     */
    public function afterResponse(string $actionId, Response $response): void
    {}

    /**
     * 获取布局名称
     *
     * @return string
     */
    public function getLayoutName(): string
    {
        return 'main';
    }

    /**
     * 获取模板名称
     *
     * @param string $actionStr            
     * @return string
     */
    public function getTemplateName(string $actionStr = ''): string
    {
        list ($moduleName, $controllerId, $actionId) = Application::$app->resolveActionId($actionStr);
        return $moduleName . '/' . $controllerId . '/' . $actionId;
    }

    private function makeTemplateObject(): ITemplate
    {
        return Application::$app->container->makeAlias('template');
    }

    public function view(array $params = [], string $actionStr = '', string $contentType = '')
    {
        $template = $this->makeTemplateObject();
        $template->setLayout($this->getLayoutName());
        $template->setTemplateName($this->getTemplateName($actionStr));
        $template->addParams($params);
        if ($contentType != '') {
            $template->setContentType($contentType);
        }
        return $template->display();
    }
}

