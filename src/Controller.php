<?php
namespace liuguang\mvc;

use Symfony\Component\HttpFoundation\Response;
use liuguang\mvc\exceptions\ServerErrorHttpException;

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
        $actionMethodName = $this->getActionMethodName($actionId);
        try {
            $methodInfo = new \ReflectionMethod($this, $actionMethodName);
            if ($methodInfo->isPublic()) {
                $response = $methodInfo->invoke($this);
            } else {
                throw new ServerErrorHttpException('操作:' . $this->getUniqueActionId() . '不存在');
            }
        } catch (\ReflectionException $e) {
            throw new ServerErrorHttpException('操作:' . $this->getUniqueActionId() . '不存在');
        }
        return $response;
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
}

