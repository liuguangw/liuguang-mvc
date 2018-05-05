<?php
namespace liuguang\mvc\command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class WelcomeCommand extends Command
{

    protected function configure()
    {
        $this->setName('welcome')->setDescription('welcome to use liuguang/mvc console tool.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('welcome to use liuguang/mvc console tool.');
        $output->writeln('<fg=green>migrate/up [n]</> :use this command to migrate data');
        $output->writeln('<fg=green>migrate/down [n]</> :use this command to remove last migration');
        $output->writeln('<fg=green>migrate/list</> :use this command to show all done migrations');
        $output->writeln('<fg=green>migrate/create <name></> :use this command to generate a new migration file');
    }
}

