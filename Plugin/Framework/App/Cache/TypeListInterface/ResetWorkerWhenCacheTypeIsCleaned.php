<?php

namespace MageSuite\ProductTileWarmup\Plugin\Framework\App\Cache\TypeListInterface;

class ResetWorkerWhenCacheTypeIsCleaned
{
    protected \Magento\Framework\FlagManager $flagManager;

    protected array $cacheTypesThatTriggerWorkerReset = ['block_html'];

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
