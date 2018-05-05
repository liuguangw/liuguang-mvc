<?php
namespace liuguang\mvc\command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Input\InputArgument;
use liuguang\mvc\db\MigrationManager;
use liuguang\mvc\db\Migration;

class MigrateDownCommand extends Command
{

    protected function configure()
    {
        $this->setName('migrate/down')->setDescription('use this command to undo migrate data');
        $this->addArgument('n', InputArgument::OPTIONAL, 'undo migrations total count', 1);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // 条数限制
        $limit = $input->getArgument('n');
        $migrationManger = new MigrationManager();
        $needUpList = $migrationManger->getNeeddownList($limit);
        if (! empty($needUpList)) {
            $names = [];
            foreach ($needUpList as $migrate) {
                $names[] = $migrate->getName();
            }
            // 确认操作
            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion('Continue with this migrations down: <info>' . implode(',', $names) . '</info> ?[Y/n]', false);
            if (! $helper->ask($input, $output, $question)) {
                $output->write('action canceled');
                return;
            }
            // 执行migration
            foreach ($needUpList as $migrate) {
                $name = $migrate->getName();
                $output->writeln('undo migrate:' . $name);
                try {
                    $this->doMigrate($migrate);
                    $output->writeln('<info>success to down migrate: ' . $name . '</info>');
                } catch (\Exception $e) {
                    $output->writeln('<error>' . $e->getMessage() . '</error>');
                }
            }
        } else {
            $output->write('<error>no migration found</error>');
        }
    }

    private function doMigrate(Migration $migration)
    {
        $migration->doDown();
    }
}

