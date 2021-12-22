<?php

namespace MageSuite\ProductTileWarmup\Worker;

class ResetChecker
{
    const MINIMUM_DELAY_BETWEEN_CHECKS_IN_SECONDS = 5;
    const MINIMUM_DELAY_BETWEEN_RESETS_IN_SECONDS = 60;

    protected $lastCheckTimestamp = null;
    protected $lastResetTimestamp = null;

    /**
     * @var DatabaseConnection
     */
    protected $databaseConnection;

    public function __construct(DatabaseConnection $databaseConnection)
    {
        $this->databaseConnection = $databaseConnection->getConnection();
        $this->lastResetTimestamp = time();
    }

    /**
     * @throws ResetException
     */
    public function check(): void
    {
        if (is_numeric($this->lastCheckTimestamp) && $this->lastCheckTimestamp + self::MINIMUM_DELAY_BETWEEN_CHECKS_IN_SECONDS >= time()) {
            return;
        }

        if (is_numeric($this->lastResetTimestamp) && $this->lastResetTimestamp + self::MINIMUM_DELAY_BETWEEN_RESETS_IN_SECONDS >= time()) {
            return;
        }

        if ($this->isResetTimestampMet()) {
            throw new ResetException();
        }
    }

    public function markResetAsDone()
    {
        $this->lastCheckTimestamp = time();
        $this->lastResetTimestamp = time();
    }

    protected function isResetTimestampMet()
    {
        $statement = $this->databaseConnection->prepare("SELECT flag_data FROM flag WHERE flag_code = ?");
        $statement->execute(['reset_warmup_worker']);

        $timestampInDatabase = $statement->fetch();

        if ($timestampInDatabase == null) {
            return false;
        }

        return $timestampInDatabase > $this->lastResetTimestamp;
    }
}
