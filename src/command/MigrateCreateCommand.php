<?php
namespace liuguang\mvc\command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use liuguang\mvc\Application;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class MigrateCreateCommand extends Command
{

    protected function configure()
    {
        $this->setName('migrate/create')->setDescription('use this command to create a migration file');
        $this->addArgument('name', InputArgument::REQUIRED, 'migrate name');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $name = $input->getArgument('name');
        $classname = 'm' . time() . '_' . rand(1000, 9999) . '_' . $name;
        // 确认操作
        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion('Continue with the migration create: <info>' . $classname . '</info> ?[Y/n]', false);
        if (! $helper->ask($input, $output, $question)) {
            $output->write('action canceled');
            return;
        }
        // 写入文件
        $content = '<?php' . PHP_EOL;
        $ns = Application::$app->config->get('migrationNs');
        if ($ns != '') {
            $content .= ('namespace ' . $ns . ';' . PHP_EOL);
        }
        $content .= ('use Doctrine\DBAL\Schema\AbstractSchemaManager;' . PHP_EOL);
        $content .= ('use liuguang\mvc\db\Migration;' . PHP_EOL . PHP_EOL);
        $content .= ('class ' . $classname . ' extends Migration' . PHP_EOL . '{' . PHP_EOL . PHP_EOL);
        $content .= ('    public function up(AbstractSchemaManager $schemaManager):void' . PHP_EOL . '    {}' . PHP_EOL . PHP_EOL);
        $content .= ('    public function down(AbstractSchemaManager $schemaManager):void' . PHP_EOL . '    {}' . PHP_EOL . PHP_EOL);
        $content .= '}';
        $savePath = Application::$app->config->get('migrationDir') . '/./' . $classname . '.php';
        $p = @file_put_contents($savePath, $content);
        if ($p === false) {
            $output->write('<error>failed to write data to ' . $savePath . '</error>');
        } else {
            $output->write('success created <info>' . $classname . '</info>');
        }
    }
}

