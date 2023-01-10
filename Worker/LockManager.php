<?php

namespace MageSuite\ProductTileWarmup\Worker;

class LockManager
{
    const LOCK_NAME = '%s_product_tile_warmup_%s';
    const DELAY_BETWEEN_CHECKS_IN_SECONDS = 600;

    protected \PDO $databaseConnection;
    protected int $lastCheckTimestamp = 0;
    protected string $databaseName = '';

    public function __construct(DatabaseConnection $databaseConnection)
    {
        $this->databaseName = $databaseConnection->getDatabaseName();
        $this->databaseConnection = $databaseConnection->getConnection();
    }

    /**
     * @throws ResetException
     */
    public function canAquireLock($groupId): bool
    {
        if ($this->lastCheckTimestamp + self::DELAY_BETWEEN_CHECKS_IN_SECONDS >= time()) {
            return true;
        }

        $lockName = sprintf(self::LOCK_NAME, $this->databaseName, $groupId);

        $statement = $this->databaseConnection->prepare("SELECT GET_LOCK('$lockName', 5)");
        $statement->execute();

        $result = $statement->fetchColumn();

        $this->lastCheckTimestamp = time();
        return (string)$result === "1";
    }
}
