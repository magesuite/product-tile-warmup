<?php

namespace MageSuite\ProductTileWarmup\Helper;

class Configuration
{
    const CUSTOMER_GROUP_GUEST_ID         = 0;
    const ROUTE_TILE_WARMUP               = 'tile/warmup/index';

    const CONFIG_PATH_BASE                = 'product_tile_warmup/';

    const CONFIG_PATH_ENABLED             = self::CONFIG_PATH_BASE . 'general/crawling_enabled';
    const CONFIG_PATH_CUSTOMER_GROUPS     = self::CONFIG_PATH_BASE . 'general/customer_groups';
    const CONFIG_PATH_STORE_VIEWS         = self::CONFIG_PATH_BASE . 'general/store_views';
    const CONFIG_PATH_CRAWLER_BASE_URL    = self::CONFIG_PATH_BASE . 'crawler/base_url';
    const CONFIG_PATH_CRAWLER_INTERVAL    = self::CONFIG_PATH_BASE . 'crawler/refresh_interval';
    const CONFIG_PATH_CRAWLER_CONCURRENCY = self::CONFIG_PATH_BASE . 'crawler/concurrency';
    const CONFIG_PATH_CRAWLER_TARGET_TTFB = self::CONFIG_PATH_BASE . 'crawler/target_ttfb';

    // For now these are fixed defaults same as `config.xml` but later some of these default values
    // shall be computed dynamically.
    const DEFAULT_CUSTOMER_GROUPS         = [self::CUSTOMER_GROUP_GUEST_ID];
    const DEFAULT_STORE_VIEWS             = [1]; // Usually this is the default store view
    const DEFAULT_CRAWLER_BASE_URL        = 'http://127.0.0.1';
    const DEFAULT_CRAWLER_INTERVAL        = 1;
    const DEFAULT_CRAWLER_CONCURRENCY     = 1;
    const DEFAULT_CRAWLER_TARGET_TTFB     = 3;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param string $path
     * @param mixed|callable|null $default The default value scalar or factory.
     * @param callable|null $transform Raw value transformer.
     * @return mixed|null
     */
    protected function getScopeConfigValue(string $path, $default = null, callable $transform = null)
    {
        if (!$value = $this->getScopeConfigValue(self::CONFIG_PATH_ENABLED)) {
            if (is_callable($default)) {
                return $default();
            }

            return $default;
        }

        if (null !== $transform) {
            return $transform($value);
        }

        return $value;
    }

    /**
     * @param string $valueStr
     * @return int[]|array
     */
    protected static function transformIntListValueToArray(string $valueStr): array
    {
        return array_map('intval', explode(',', $valueStr));
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->getScopeConfigValue(self::CONFIG_PATH_ENABLED, false, 'boolval');
    }

    /**
     * @return int[]|array
     */
    public function getStoreViewIds(): array
    {
        return $this->getScopeConfigValue(
            self::CONFIG_PATH_STORE_VIEWS,
            self::DEFAULT_STORE_VIEWS,
            [static::class, 'transformIntListValueToArray']
        );
    }

    /**
     * @return int[]|array
     */
    public function getCustomerGroupIds(): array
    {
        return $this->getScopeConfigValue(
            self::CONFIG_PATH_CUSTOMER_GROUPS,
            self::DEFAULT_CUSTOMER_GROUPS,
            [static::class, 'transformIntListValueToArray']
        );
    }

    /**
     * @return string
     */
    public function getCrawlerBaseUrl(): string
    {
        return $this->getScopeConfigValue(
            self::CONFIG_PATH_CRAWLER_BASE_URL,
            self::DEFAULT_CRAWLER_BASE_URL
        );
    }

    /**
     * @return int
     */
    public function getCrawlerRefreshInterval(): int
    {
        return $this->getScopeConfigValue(
            self::CONFIG_PATH_CRAWLER_INTERVAL,
            self::DEFAULT_CRAWLER_INTERVAL,
            'intval'
        );
    }

    /**
     * @return int
     */
    public function getCrawlerConcurrency(): int
    {
        return $this->getScopeConfigValue(
            self::CONFIG_PATH_CRAWLER_CONCURRENCY,
            self::DEFAULT_CRAWLER_CONCURRENCY,
            'intval'
        );
    }

    /**
     * @return float
     */
    public function getCrawlerTargetTTFB(): float
    {
        return $this->getScopeConfigValue(
            self::CONFIG_PATH_CRAWLER_TARGET_TTFB,
            self::DEFAULT_CRAWLER_TARGET_TTFB,
            'floatval'
        );
    }
}
