<?php
namespace liuguang\mvc\command;

use Symfony\Component\Console\Application as ConsoleApplication;
$application = new ConsoleApplication();
$application->setCommandLoader(getContainer()->makeAlias('commandLoader'));
$application->setDefaultCommand('welcome');
$application->run();