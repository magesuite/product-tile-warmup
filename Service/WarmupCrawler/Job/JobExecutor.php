<?php

namespace MageSuite\ProductTileWarmup\Service\WarmupCrawler\Job;

use GuzzleHttp\Client;
use MageSuite\WarmupCrawler\Service\Session\SessionProviderInterface;
use Psr\Log\LoggerInterface;

class JobExecutor extends \MageSuite\WarmupCrawler\Service\Job\JobExecutor
{
    public function __construct(
        LoggerInterface $logger,
        Client $client,
        SessionProviderInterface $sessions
    ) {
        parent::__construct($logger, $client, $sessions);
    }
}
