<?php
namespace liuguang\mvc\handlers;

use Symfony\Component\HttpFoundation\Response;
use liuguang\mvc\Application;

class DefaultTemplate implements ITemplate
{

    /**
     *
     * @var TemplateEngine
     */
    private $template;

    private $contentType;

    public function __construct()
    {
        $config = Application::$app->config;
        $template = new TemplateEngine('_', $config->get('templateDir'));
        $template->setForceRebuild($config->get('forceRebuildTemplate'));
        $this->template = $template;
        $this->contentType = $config->get('defaultContentType');
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \liuguang\mvc\handlers\ITemplate::setLayout()
     */
    public function setLayout(string $layout): void
    {
        $this->template->setLayout($layout);
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \liuguang\mvc\handlers\ITemplate::setTemplateName()
     */
    public function setTemplateName(string $templateName): void
    {
        $this->template->setTemplateName($templateName);
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \liuguang\mvc\handlers\ITemplate::setForceRebuild()
     */
    public function setForceRebuild(bool $forceRebuild): void
    {
        $this->template->setForceRebuild($forceRebuild);
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \liuguang\mvc\handlers\ITemplate::setContentType()
     */
    public function setContentType(string $contentType): void
    {
        $this->contentType = $contentType;
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \liuguang\mvc\handlers\ITemplate::addParams()
     */
    public function addParams(array $params): void
    {
        $this->template->addParams($params);
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \liuguang\mvc\handlers\ITemplate::setParam()
     */
    public function setParam(string $key, $value): void
    {
        $this->template->setParam($key, $value);
    }

    /**
     * 获取输出
     *
     * @throws Exception
     * @return string
     */
    private function getOutput(): string
    {
        ob_start();
        try {
            $this->template->display();
        } catch (\Exception $e) {
            ob_end_clean();
            throw $e;
        }
        return ob_get_clean();
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \liuguang\mvc\handlers\ITemplate::display()
     */
    public function display(): Response
    {
        $response = new Response($this->getOutput());
        $response->headers->set('Content-Type', $this->contentType);
        return $response;
    }
}

