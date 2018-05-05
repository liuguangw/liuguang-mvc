<?php
namespace liuguang\mvc\command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use liuguang\mvc\Application;
use liuguang\mvc\db\MigrationLoger;
use Symfony\Component\Console\Helper\Table;

class MigrateListCommand extends Command
{

    protected function configure()
    {
        $this->setName('migrate/list')->setDescription('use this command to show all done migrations');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $conn = Application::$app->getDb();
        $schemaManager = $conn->getSchemaManager();
        $loger = new MigrationLoger();
        $output->writeln('all done migrations list');
        $list = $loger->getAllMigrations();
        $rows = [];
        foreach ($list as $info) {
            $rows[] = [
                $info['id'],
                $info['name'],
                date('Y-m-d H:i:s', $info['created_at'])
            ];
        }
        if (! empty($rows)) {
            $table = new Table($output);
            $table->setHeaders([
                'id',
                'name',
                'time'
            ])->setRows($rows);
            $table->render();
        } else {
            $output->write('<info>no migrations</info>');
        }
    }
}

