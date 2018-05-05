<?php
namespace liuguang\mvc\command;

use Symfony\Component\Console\Application as ConsoleApplication;
$application = new ConsoleApplication();
$command = new WelcomeCommand();
$application->add($command);
$application->setDefaultCommand($command->getName());
$application->add(new MigrateUpCommand());
$application->add(new MigrateDownCommand());
$application->add(new MigrateListCommand());
$application->add(new MigrateCreateCommand());
$application->run();