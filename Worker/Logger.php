<?php

namespace MageSuite\ProductTileWarmup\Worker;

class Logger
{
    protected bool $debugMode = false;
    protected bool $outputToConsole = false;
    /**
     * @var \Monolog\Logger
     */
    protected $monolog;

    public function __construct(bool $debugMode = false, bool $outputToConsole = false)
    {
        $this->debugMode = $debugMode;
        $this->outputToConsole = $outputToConsole;

        $this->monolog = new \Monolog\Logger('tile_warmup_debug');

        $streamHandler = new \Monolog\Handler\StreamHandler(BP . '/var/log/tile_warmup_debug.log', \Monolog\Logger::DEBUG);
        $this->monolog->pushHandler($streamHandler);
    }

    public function log($message)
    {
        if ($this->outputToConsole) {
            echo $message . PHP_EOL; // phpcs:ignore
            return;
        }

        if (!$this->debugMode) {
            return;
        }

        $this->monolog->debug($message);
    }
}
