<?php

namespace MageSuite\ProductTileWarmup\Helper;

class Configuration
{
    const CUSTOMER_GROUP_GUEST_ID = 0;
    const ROUTE_TILE_WARMUP = 'tile/warmup/index';

    const CONFIG_PATH_ENABLED = 'product_tile_warmup/general/crawling_enabled';
    const CONFIG_PATH_CUSTOMER_GROUPS = 'product_tile_warmup/general/customer_groups';
    const CONFIG_PATH_DISABLED_STORE_VIEWS = 'product_tile_warmup/general/disabled_store_views';

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    public function __construct(\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return (bool)$this->scopeConfig->getValue(self::CONFIG_PATH_ENABLED);
    }

    /**
     * @return int[]|array
     */
    public function getDisabledStoreViewIds(): array
    {
        $value = $this->scopeConfig->getValue(self::CONFIG_PATH_DISABLED_STORE_VIEWS);

        if ($value === null) {
            return [];
        }

        return explode(',', $value);
    }

    /**
     * @return int[]|array
     */
    public function getCustomerGroupIds(): array
    {
        $value = $this->scopeConfig->getValue(self::CONFIG_PATH_CUSTOMER_GROUPS);

        if ($value === null) {
            return [];
        }

        return explode(',', $value);
    }
}
