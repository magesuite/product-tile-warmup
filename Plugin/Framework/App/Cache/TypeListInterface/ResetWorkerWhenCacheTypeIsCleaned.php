<?php

namespace MageSuite\ProductTileWarmup\Plugin\Framework\App\Cache\TypeListInterface;

class ResetWorkerWhenCacheTypeIsCleaned
{
    /**
     * @var \Magento\Framework\FlagManager
     */
    protected $flagManager;

    protected $cacheTypesThatTriggerWorkerReset = ['block_html'];

    public function __construct(\Magento\Framework\FlagManager $flagManager)
    {
        $this->flagManager = $flagManager;
    }

    public function afterCleanType(\Magento\Framework\App\Cache\TypeListInterface $subject, $result, $typeCode)
    {
        if (in_array(trim($typeCode), $this->cacheTypesThatTriggerWorkerReset)) {
            $this->flagManager->saveFlag('reset_warmup_worker', time());
        }

        return $result;
    }
}
