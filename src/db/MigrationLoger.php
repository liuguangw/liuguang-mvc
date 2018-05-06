<?php
namespace liuguang\mvc\db;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\FetchMode;
use liuguang\mvc\Application;

class MigrationLoger
{

    /**
     *
     * @var Connection
     */
    private $conn;

    /**
     *
     * @var string
     */
    private $tableName;

    public function __construct()
    {
        $config = Application::$app->config;
        $connIndex = $config->get('migrationLogerConn', $config->get('appConn'));
        $this->conn = Application::$app->getDb($connIndex);
        $this->tableName = $config->get('migrationTable');
        $schemaManager = $this->conn->getSchemaManager();
        if (! $schemaManager->tablesExist($this->tableName)) {
            $this->initTable($schemaManager);
        }
    }

    /**
     * 初始化迁移表
     *
     * @param AbstractSchemaManager $schemaManager            
     * @return void
     */
    private function initTable(AbstractSchemaManager $schemaManager): void
    {
        $scheme = new Schema();
        $newTable = $scheme->createTable($this->tableName);
        $newTable->addColumn('id', 'integer', [
            'unsigned' => true,
            'autoincrement' => true
        ]);
        $newTable->setPrimaryKey([
            'id'
        ]);
        $newTable->addColumn('name', 'string', [
            'length' => 60,
            'comment' => '名称'
        ]);
        $newTable->addColumn('conn', 'integer', [
            'unsigned' => true,
            'comment' => '连接id'
        ]);
        $newTable->addColumn('created_at', 'integer', [
            'unsigned' => true,
            'comment' => '执行时间'
        ]);
        $newTable->addUniqueIndex([
            'conn',
            'name'
        ]);
        $schemaManager->createTable($newTable);
    }

    public function addMigration(Migration $migrate): void
    {
        $this->conn->insert($this->tableName, [
            'name' => $migrate->getName(),
            'conn' => $migrate->getConnectionIndex(),
            'created_at' => time()
        ]);
    }

    public function removeMigration(Migration $migrate): void
    {
        $this->conn->createQueryBuilder()
            ->delete($this->tableName)
            ->where('name = ?')
            ->andWhere('conn = ?')
            ->setParameter(0, $migrate->getName())
            ->setParameter(1, $migrate->getConnectionIndex())
            ->execute();
    }

    public function getLastMigrations(int $limit = 1): array
    {
        $builder = $this->conn->createQueryBuilder();
        $builder->select('*')
            ->from($this->tableName)
            ->orderBy('id', 'DESC');
        if ($limit > 0) {
            $builder->setMaxResults($limit);
        }
        $stm = $builder->execute();
        return $stm->fetchAll(FetchMode::ASSOCIATIVE);
    }

    public function getAllMigrations(): array
    {
        $stm = $this->conn->createQueryBuilder()
            ->select('*')
            ->from($this->tableName)
            ->orderBy('id', 'ASC')
            ->execute();
        return $stm->fetchAll(FetchMode::ASSOCIATIVE);
    }
}

