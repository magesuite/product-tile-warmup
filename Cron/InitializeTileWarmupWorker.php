<?php

namespace MageSuite\ProductTileWarmup\Cron;

class InitializeTileWarmupWorker
{
    protected ?\MageSuite\ProductTileWarmup\Service\CliWorkerInitializer $cliWorkerInitializer;

    public function __construct(\MageSuite\ProductTileWarmup\Service\CliWorkerInitializer $cliWorkerInitializer)
    {
        $this->cliWorkerInitializer = $cliWorkerInitializer;
    }

    public function execute()
    {
        $this->cliWorkerInitializer->initialize();
    }
}
