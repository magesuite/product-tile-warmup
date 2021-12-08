<?php
// main entrypoint for a worker that does infinite loop and requests
define('BP', realpath(__DIR__ . '/../../../../'));

require_once BP . '/vendor/autoload.php';

$application = new \Symfony\Component\Console\Application();
$application->add(new \MageSuite\ProductTileWarmup\Worker\WorkerCommand());
$application->run();
