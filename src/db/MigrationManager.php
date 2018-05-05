<?php
namespace liuguang\mvc\db;

use liuguang\mvc\Application;

class MigrationManager
{

    /**
     *
     * @var MigrationLoger
     */
    private $loger;

    public function __construct()
    {
        $this->loger = new MigrationLoger();
    }

    private function getMigrate(string $name)
    {
        $nameNs = Application::$app->config->get('migrationNs');
        $classname = $name;
        if ($nameNs != '') {
            $classname = $nameNs . '\\' . $name;
        }
        return new $classname();
    }

    /**
     * 获取列表
     *
     * @return Migration[]
     */
    private function scanMigrations(): array
    {
        $migrationDir = Application::$app->config->get('migrationDir');
        $migrations = [];
        if (! is_dir($migrationDir)) {
            return $migrations;
        }
        $files = scandir($migrationDir);
        foreach ($files as $fileName) {
            $path = $migrationDir . '/./' . $fileName;
            if (! is_file($path)) {
                continue;
            }
            if (preg_match('/^(.+?)\.php$/', $fileName, $matchData) != 0) {
                $migrationObj = $this->getMigrate($matchData[1]);
                $migrations[$matchData[1] . '#' . $migrationObj->getConnectionIndex()] = $migrationObj;
            }
        }
        return $migrations;
    }

    /**
     *
     * @param int $limit            
     * @return Migration[]
     */
    public function getNeedupList(int $limit = 0): array
    {
        $migrations = $this->scanMigrations();
        $logMigrations = $this->loger->getAllMigrations();
        foreach ($logMigrations as $info) {
            $identify = $info['name'] . '#' . $info['conn'];
            if (isset($migrations[$identify])) {
                unset($migrations[$identify]);
            }
        }
        $result = array_values($migrations);
        if ($limit > 0) {
            $result = array_slice($result, 0, $limit);
        }
        return $result;
    }

    /**
     *
     * @param int $limit            
     * @return Migration[]
     */
    public function getNeeddownList(int $limit = 0): array
    {
        $result = [];
        $logMigrations = $this->loger->getLastMigrations($limit);
        foreach ($logMigrations as $info) {
            $result[]=$this->getMigrate($info['name']);
        }
        return $result;
    }
}

