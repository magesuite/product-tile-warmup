<?php

namespace MageSuite\ProductTileWarmup\Worker;

class Logger
{
    protected $debugMode = false;

    /**
     * @var \Monolog\Logger
     */
    protected $monolog;

    public function __construct(bool $debugMode = false)
    {
        $this->debugMode = $debugMode;
        $this->monolog = new \Monolog\Logger('tile_warmup_debug');

        $streamHandler = new \Monolog\Handler\StreamHandler(BP . '/var/log/tile_warmup_debug.log', \Monolog\Logger::DEBUG);
        $this->monolog->pushHandler($streamHandler);
    }

    public function log($message)
    {
        if (!$this->debugMode) {
            return;
        }

        $this->monolog->debug($message);
    }
}
