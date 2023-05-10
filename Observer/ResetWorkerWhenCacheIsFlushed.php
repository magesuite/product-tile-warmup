<?php

namespace MageSuite\ProductTileWarmup\Observer;

class ResetWorkerWhenCacheIsFlushed implements \Magento\Framework\Event\ObserverInterface
{
    protected \Magento\Framework\FlagManager $flagManager;

    public function __construct(\Magento\Framework\FlagManager $flagManager)
    {
        $this->flagManager = $flagManager;
    }

    /**
     * @inheritDoc
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $this->flagManager->saveFlag('reset_warmup_worker', time());
    }
}
