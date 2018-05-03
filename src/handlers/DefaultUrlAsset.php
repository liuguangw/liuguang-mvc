<?php
namespace liuguang\mvc\handlers;

use Symfony\Component\Asset\Package;
use liuguang\mvc\Application;
use Symfony\Component\Asset\PathPackage;
use Symfony\Component\Asset\VersionStrategy\StaticVersionStrategy;

class DefaultUrlAsset extends UrlAsset
{

    /**
     * 默认版本
     *
     * @var string
     */
    private $version;

    public function __construct()
    {
        $this->version = Application::$app->config->get('STATIC_URL_VERSION', 'v' . date('Ym'));
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \liuguang\mvc\handlers\UrlAsset::getDefaultPackage()
     */
    public function getDefaultPackage(): Package
    {
        return new PathPackage(Application::$app->publicContext, new StaticVersionStrategy($this->version));
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \liuguang\mvc\handlers\UrlAsset::getNamedPackages()
     */
    public function getNamedPackages(): array
    {
        return [];
    }
}

