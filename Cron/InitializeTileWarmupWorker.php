<?php

namespace MageSuite\ProductTileWarmup\Cron;

class InitializeTileWarmupWorker
{
    /**
     * @var \MageSuite\ProductTileWarmup\Service\CliWorkerInitializer
     */
    protected $cliWorkerInitializer;

    public function __construct(\MageSuite\ProductTileWarmup\Service\CliWorkerInitializer $cliWorkerInitializer) {
        $this->cliWorkerInitializer = $cliWorkerInitializer;
    }

    public function execute() {
        $this->cliWorkerInitializer->initialize();
    }
}
