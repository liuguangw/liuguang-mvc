<?php
namespace liuguang\mvc\db;

use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Connection;
use liuguang\mvc\Application;

abstract class Migration
{

    public function getConnectionIndex(): int
    {
        return 0;
    }

    public final function getName(): string
    {
        $classname = get_class($this);
        $pos = strrpos($classname, '\\');
        if ($pos === false) {
            return $classname;
        }
        return substr($classname, $pos + 1);
    }

    protected function getConnection(): Connection
    {
        return Application::$app->getDb($this->getConnectionIndex());
    }

    protected function getSchemaManager(): AbstractSchemaManager
    {
        return $this->getConnection()->getSchemaManager();
    }

    protected function getLoger(): MigrationLoger
    {
        return new MigrationLoger();
    }

    protected function logMigration(bool $isUp)
    {
        if ($isUp) {
            $this->getLoger()->addMigration($this);
        } else {
            $this->getLoger()->removeMigration($this);
        }
    }

    /**
     *
     * @throws Exception
     */
    public function doUp(): void
    {
        $conn = $this->getConnection();
        $schemaManager = $conn->getSchemaManager();
        $conn->beginTransaction();
        try {
            $this->up($schemaManager);
            $this->logMigration(true);
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollBack();
            throw $e;
        }
    }

    public abstract function up(AbstractSchemaManager $schemaManager): void;

    /**
     *
     * @throws Exception
     */
    public function doDown(): void
    {
        $conn = $this->getConnection();
        $schemaManager = $conn->getSchemaManager();
        $conn->beginTransaction();
        try {
            $this->down($schemaManager);
            $this->logMigration(false);
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollBack();
            throw $e;
        }
    }

    public abstract function down(AbstractSchemaManager $schemaManager): void;
}

