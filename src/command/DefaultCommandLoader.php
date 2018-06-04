<?php
namespace liuguang\mvc\command;

/**
 * 框架默认的命令加载
 *
 * @author liuguang
 *        
 */
class DefaultCommandLoader extends CommandLoader
{

    public function __construct()
    {
        parent::__construct([]);
        $this->set('migrate/up', function () {
            return new MigrateUpCommand();
        });
        $this->set('migrate/down', function () {
            return new MigrateDownCommand();
        });
        $this->set('migrate/list', function () {
            return new MigrateListCommand();
        });
        $this->set('migrate/create', function () {
            return new MigrateCreateCommand();
        });
        $this->set('welcome', function () {
            return new WelcomeCommand();
        });
    }
}

