<?php

declare(strict_types=1);

namespace MageSuite\ProductTileWarmup\Plugin\Controller\DisableProfilerInterface;

class Tideways
{
    public function beforeExecute(\MageSuite\ProductTileWarmup\Controller\DisableProfilerInterface $subject)
    {
        if (class_exists('Tideways\Profiler')) {
            \Tideways\Profiler::stop();
        }
    }
}
