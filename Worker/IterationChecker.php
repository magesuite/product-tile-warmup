<?php

declare(strict_types=1);

namespace MageSuite\ProductTileWarmup\Worker;

class IterationChecker
{
    protected const DEFAULT_TIME_ITERATION_LIMIT = 24;
    protected $databaseConnection;
    protected int $startTime;

    public function __construct(
        DatabaseConnection $databaseConnection
    ) {
        $this->databaseConnection = $databaseConnection->getConnection();
        $this->startTime = time();
    }

    public function check(): void
    {
        $timeIterationLimit = $this->getTimeIterationLimit() * 3600;

        if (empty($timeIterationLimit)) {
            return;
        }

        if ($this->startTime +  $timeIterationLimit <= time()) {
            throw new TimeLimitException('Iteration time limit exceeded');
        }
    }

    protected function getTimeIterationLimit(): ?int
    {
        $statement = $this->databaseConnection->prepare("SELECT value FROM core_config_data WHERE path = ?");
        $statement->execute([\MageSuite\ProductTileWarmup\Helper\Configuration::XML_PATH_GENERAL_TIME_ITERATION_LIMIT]);

        $value = $statement->fetch();

        if ($value == null) {
            return self::DEFAULT_TIME_ITERATION_LIMIT;
        }

        return (int) $value['value'];
    }
}
