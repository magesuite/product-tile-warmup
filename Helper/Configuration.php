<?php

namespace MageSuite\ProductTileWarmup\Helper;

class Configuration
{
    const ROUTE_TILE_WARMUP = 'tile/warmup/index';

    const CONFIG_PATH_ENABLED = 'product_tile_warmup/general/crawling_enabled';
    const CONFIG_PATH_DEBUG_MODE_ENABLED = 'product_tile_warmup/general/debug_mode_enabled';
    const CONFIG_PATH_CUSTOMER_GROUPS = 'product_tile_warmup/general/customer_groups';
    const CONFIG_PATH_DISABLED_STORE_VIEWS = 'product_tile_warmup/general/disabled_store_views';
    const CONFIG_PATH_WORKER_PROCESSES_CONFIGURATION = 'product_tile_warmup/general/worker_processes_configuration';

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Framework\Serialize\SerializerInterface
     */
    protected $serializer;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Serialize\SerializerInterface $serializer
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->serializer = $serializer;
    }

    public function isEnabled(): bool
    {
        return (bool)$this->scopeConfig->getValue(self::CONFIG_PATH_ENABLED);
    }

    public function isDebugModeEnabled(): bool
    {
        return (bool)$this->scopeConfig->getValue(self::CONFIG_PATH_DEBUG_MODE_ENABLED);
    }

    public function getDisabledStoreViewIds(): array
    {
        $value = $this->scopeConfig->getValue(self::CONFIG_PATH_DISABLED_STORE_VIEWS);

        if ($value === null) {
            return [];
        }

        return explode(',', $value);
    }

    public function getCustomerGroupIds(): array
    {
        $value = $this->scopeConfig->getValue(self::CONFIG_PATH_CUSTOMER_GROUPS);

        if ($value === null) {
            return [];
        }

        return explode(',', $value);
    }

    public function getWorkerProcessesConfiguration(): array
    {
        $value = $this->scopeConfig->getValue(self::CONFIG_PATH_WORKER_PROCESSES_CONFIGURATION);

        if ($value === null) {
            return [];
        }

        return $this->serializer->unserialize($value);
    }
}
