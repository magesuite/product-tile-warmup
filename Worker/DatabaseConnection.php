<?php

namespace MageSuite\ProductTileWarmup\Worker;

class DatabaseConnection
{
    protected \PDO $connection;
    protected string $databaseName = '';

    public function __construct(string $envFilePath)
    {
        $config = include $envFilePath; // phpcs:ignore

        $this->initializeConnection($config['db']['connection']['default']);
    }

    public function getDatabaseName(): string
    {
        return $this->databaseName;
    }

    public function getConnection(): \PDO
    {
        return $this->connection;
    }

    /**
     * @param $config
     */
    public function initializeConnection($databaseConfig): void
    {
        $host = $databaseConfig['host'];
        $this->databaseName = $databaseConfig['dbname'];
        $username = $databaseConfig['username'];
        $password = $databaseConfig['password'];

        $this->connection = new \PDO(
            sprintf('mysql:host=%s;dbname=%s', $host, $this->getDatabaseName()),
            $username,
            $password
        );
    }
}
