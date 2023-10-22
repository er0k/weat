<?php

namespace Weat;

use SQLite3;
use Weat\Config;
use Weat\Exception;

class WeatherStorage
{
    private ?SQLite3 $db = null;

    public function __construct(Config $config)
    {
        $this->db = $this->getDb($config->weat_db);
    }

    public function save(int $service, array $data): Void
    {
        $query = "INSERT into weat VALUES (:service, :data)";
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':service', $service, SQLITE3_INTEGER);
        $stmt->bindValue(':data', json_encode($data), SQLITE3_TEXT);

        $saved = $stmt->execute();

        if (!$saved) {
            error_log("failed to save data for service $service");
        }
    }

    public function fetchLatest(int $service)
    {
        $query = "SELECT data from weat WHERE service = :service ORDER BY rowid DESC LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':service', $service, SQLITE3_INTEGER);

        $result = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

        return $result['data'];
    }

    private function getDb(string $dbPath)
    {
        if ($this->db) {
            return $this->db;
        }

        $db = new SQLite3($this->getDbFile($dbPath));

        $this->createTable($db);

        return $db;
    }

    private function getDbFile(string $dbPath): string
    {
        if (!is_file($dbPath)) {
            touch($dbPath);
        }

        if (!is_writable($dbPath)) {
            throw new Exception("Can't write to database store: {$dbPath}");
        }

        return $dbPath;
    }

    private function createTable(SQLite3 $db)
    {
        // one table, two cols: service, data
        // service is an int that corresponds to WeatherService::TYPES
        // data is arbitrary JSON data however it comes back from the $service
        $existsSql = "SELECT name FROM sqlite_master WHERE type = 'table' AND name = 'weat'";
        $tableExists = $db->query($existsSql)->fetchArray();

        if ($tableExists) {
            return;
        }

        $createdTable = $db->query("CREATE TABLE weat (service INT NOT NULL, data JSON NOT NULL)");

        if (!$createdTable) {
            throw new Exception("Could not create db table :(");
        }
    }
}
