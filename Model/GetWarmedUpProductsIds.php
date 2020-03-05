<?php

namespace MageSuite\ProductTileWarmup\Model;

class GetWarmedUpProductsIds
{
    const REDIS_KEY_PREFIX = 'zc:k:ab3_';
    const MAX_AMOUNT_OF_KEYS_PER_SCAN = 500;

    /**
     * @var \MageSuite\ProductTile\Service\CacheKeyPrefixGenerator
     */
    protected $cacheKeyPrefixGenerator;

    /**
     * @var \Magento\Framework\App\Cache
     */
    protected $cache;

    public function __construct(
        \MageSuite\ProductTile\Service\CacheKeyPrefixGenerator $cacheKeyPrefixGenerator,
        \Magento\Framework\App\Cache $cache
    )
    {
        $this->cacheKeyPrefixGenerator = $cacheKeyPrefixGenerator;
        $this->cache = $cache;
    }

    public function execute()
    {
        $frontend = $this->cache->getFrontend();
        $backend = $frontend->getBackend();

        if(!$this->isRedisCacheBackend($backend)) {
            return [];
        }

        /** @var \Credis_Client $redis */
        $redis = $this->getCredisClient($backend);

        $prefix = self::REDIS_KEY_PREFIX . $this->cacheKeyPrefixGenerator->generate().'_';
        $prefixLength = strlen($prefix);
        $pattern = $prefix .'*';
        $ids = [];
        $iterator = 0;

        do {
            $cacheKeys = $redis->scan($iterator, $pattern, self::MAX_AMOUNT_OF_KEYS_PER_SCAN);

            $productIds = array_map(function ($key) use($prefixLength) {
                $key = substr($key, $prefixLength);
                return (int)explode('_', $key)[0];
            }, $cacheKeys);

            $ids = array_merge($ids, $productIds);
        } while ($iterator != 0);

        return $ids;
    }

    /**
     * To use Redis SCAN command we need Credis_Client configured by Cache backend class
     * That property is protected so we need to hack its retrieval using Reflection API
     */
    protected function getCredisClient($backend)
    {
        $reflection = new \ReflectionClass($backend);

        $property = $reflection->getProperty('_redis');
        $property->setAccessible(true);

        return $property->getValue($backend);
    }

    public function isRedisCacheBackend($backend)
    {
        return ($backend instanceof \Cm_Cache_Backend_Redis);
    }
}