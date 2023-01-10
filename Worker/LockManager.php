<?php

namespace MageSuite\ProductTileWarmup\Worker;

class LockManager
{
    const LOCK_NAME = 'product_tile_warmup_%s';
    const DELAY_BETWEEN_CHECKS_IN_SECONDS = 600;

    /**
     * @var DatabaseConnection
     */
    protected $databaseConnection;

    protected int $lastCheckTimestamp = 0;

    public function __construct(DatabaseConnection $databaseConnection)
    {
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

        $lockName = sprintf(self::LOCK_NAME, $groupId);

        $statement = $this->databaseConnection->prepare("SELECT GET_LOCK('$lockName', 5)");
        $statement->execute();

        $result = $statement->fetchColumn();

        $this->lastCheckTimestamp = time();
        return (string)$result === "1";
    }
}
