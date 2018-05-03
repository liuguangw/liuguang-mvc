<?php
namespace liuguang\mvc\handlers;

use Symfony\Component\HttpFoundation\Response;

interface ITemplate
{

    public function setLayout(string $layout): void;

    public function setTemplateName(string $templateName): void;

    public function setForceRebuild(bool $forceRebuild): void;

    public function setContentType(string $contentType): void;

    public function addParams(array $params): void;

    public function setParam(string $key, $value): void;

    public function display(): Response;
}

