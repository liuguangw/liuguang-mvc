<?php
namespace liuguang\mvc\handlers;

use liuguang\template\TemplateEngine as SuperTemplateEngine;
use liuguang\mvc\Application;

class TemplateEngine extends SuperTemplateEngine
{

    private function getUrlAsset(): UrlAsset
    {
        return Application::$app->container->makeAlias('urlAsset');
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \liuguang\template\TemplateEngine::hasExtendRule()
     */
    protected function hasExtendRule(): bool
    {
        return true;
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \liuguang\template\TemplateEngine::extendTemplate()
     */
    protected function extendTemplate(string &$tplContent)
    {
        // /处理url标签
        // /
        // /{url}image.png{/url}
        // /{url js}path/to/common.js{/url}
        // /
        $this->processUrlTag($tplContent);
    }

    private function replaseUrlTag($match)
    {
        $matchName = $match[2];
        $matchPath = $match[3];
        $urlAsset = $this->getUrlAsset();
        if ($matchName == '') {
            return $this->getUrlAsset()->getUrl($matchPath);
        } else {
            return $this->getUrlAsset()->getUrl($matchPath, $matchName);
        }
    }

    /**
     * 处理静态资源
     *
     * @param string $content
     *            原模板内容
     * @return void
     */
    protected function processUrlTag(string &$content): void
    {
        $pattern = $this->getTagPattern('url(\s+(.+?))?' . preg_quote($this->endTag, '/') . '(.+?)' . preg_quote($this->startTag . '/url', '/'));
        $content = preg_replace_callback($pattern, [
            $this,
            'replaseUrlTag'
        ], $content);
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \liuguang\template\TemplateEngine::display()
     */
    public function display(): void
    {
        $this->renderContent();
    }
}

