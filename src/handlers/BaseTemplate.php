<?php
namespace liuguang\mvc\handlers;

use Symfony\Component\HttpFoundation\Response;
use liuguang\mvc\Application;

class BaseTemplate implements ITemplate
{

    private $layout = '';

    private $layoutDir;

    private $sourceDir;

    private $distDir;

    private $params = [];

    protected $sourceFileSuffix = '.tpl';

    protected $distFileSuffix = '.php';

    protected $contentType;

    private $templateName = null;

    private $sourcePath = null;

    private $distPath = null;

    private $layoutPath = null;

    private $useCache;

    public function __construct()
    {
        $config = Application::$app->config;
        $baseDir = $config->get('templateDir');
        $this->layoutDir = $baseDir . '/./layout';
        $this->sourceDir = $baseDir . '/./src';
        $this->distDir = $baseDir . '/./dist';
        $this->useCache = $config->get('useTemplateCache', false);
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
        $this->layout = $layout;
        if ($layout == '') {
            $this->layoutPath = null;
        } else {
            $this->layoutPath = $this->layoutDir . '/./' . $layout . $this->sourceFileSuffix;
        }
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \liuguang\mvc\handlers\ITemplate::setTemplateName()
     */
    public function setTemplateName(string $templateName): void
    {
        $this->templateName = $templateName;
        $this->sourcePath = $this->sourceDir . '/./' . str_replace('.', '/', $templateName) . $this->sourceFileSuffix;
        $this->distPath = $this->distDir . '/./' . str_replace('.', '/', $templateName) . $this->distFileSuffix;
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \liuguang\mvc\handlers\ITemplate::setContentType()
     */
    public function setContentType(string $contentType)
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
        $this->params = array_merge($this->params, $params);
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \liuguang\mvc\handlers\ITemplate::setParam()
     */
    public function setParam(string $key, $value): void
    {
        $this->params[$key] = $value;
    }

    public function getCompileContent(): string
    {
        $content = @file_get_contents($this->sourcePath);
        if ($content === false) {
            throw new \Exception('读取模板' . $this->templateName . '失败');
        }
        if ($this->layoutPath != null) {
            $layoutContent = @file_get_contents($this->layoutPath);
            if ($layoutContent === false) {
                throw new \Exception('读取布局' . $this->layout . '失败');
            }
            $content = str_replace('{content}', $content, $layoutContent);
        }
        // todo 处理模板标签
        return $content;
    }

    protected function templateCacheExists(): bool
    {
        if ($this->useCache) {
            return is_file($this->distPath);
        }
        return false;
    }

    public function getTargetPath(): string
    {
        if (! $this->templateCacheExists()) {
            $distFileDir = dirname($this->distPath);
            if (! is_dir($distFileDir)) {
                mkdir($distFileDir, 0755, true);
            }
            $p = file_put_contents($this->distPath, $this->getCompileContent());
            if ($p === false) {
                throw new \Exception('模板' . $this->templateName . '缓存写入失败');
            }
        }
        return $this->distPath;
    }

    public function getOutput(): string
    {
        ob_start();
        extract($this->params);
        try {
            include $this->getTargetPath();
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

