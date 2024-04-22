<?php

declare(strict_types=1);

namespace MageSuite\ProductTileWarmup\Plugin\Controller\DisableProfilerInterface;

class NewRelic
{
    public function beforeExecute(\MageSuite\ProductTileWarmup\Controller\DisableProfilerInterface $subject)
    {
        if (extension_loaded('newrelic')) {
            newrelic_ignore_transaction();
        }
    }
}
