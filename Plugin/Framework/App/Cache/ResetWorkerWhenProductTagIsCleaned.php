<?php

namespace MageSuite\ProductTileWarmup\Plugin\Framework\App\Cache;

class ResetWorkerWhenProductTagIsCleaned
{
    /**
     * @var \Magento\Framework\FlagManager
     */
    protected $flagManager;

    public function __construct(\Magento\Framework\FlagManager $flagManager)
    {
        $this->flagManager = $flagManager;
    }

    public function afterClean(\Magento\Framework\App\Cache $subject, $result, $tags = [])
    {
        if (empty($tags)) {
            return $result;
        }

        if ($this->productTagIsPresent($tags)) {
            $this->flagManager->saveFlag('reset_warmup_worker', time());
        }

        return $result;
    }

    /**
     * @param array|string $tags
     * @return bool
     */
    public function productTagIsPresent($tags): bool
    {
        if (!is_array($tags)) {
            $tags = [$tags];
        }

        foreach ($tags as $tag) {
            if (preg_match('/cat_p_([0-9]*)/si', $tag)) {
                return true;
            }
        }

        return false;
    }
}
