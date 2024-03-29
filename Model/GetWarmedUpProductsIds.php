<?php

namespace MageSuite\ProductTileWarmup\Model;

class GetWarmedUpProductsIds
{
    const REDIS_KEY_PREFIX = 'zc:k:???_';
    const MAX_AMOUNT_OF_KEYS_PER_SCAN = 500;

    protected \MageSuite\ProductTile\Service\CacheKeyPrefixGenerator $cacheKeyPrefixGenerator;

    protected \Magento\Framework\App\Cache $cache;

    public function __construct(
        \MageSuite\ProductTile\Service\CacheKeyPrefixGenerator $cacheKeyPrefixGenerator,
        \Magento\Framework\App\Cache $cache
    ) {
        $this->cacheKeyPrefixGenerator = $cacheKeyPrefixGenerator;
        $this->cache = $cache;
    }

    /**
     * @return array|int[]
     * @throws \ReflectionException
     */
    public function execute(): array
    {
        $frontend = $this->cache->getFrontend();
        $backend = $frontend->getBackend();

        if (get_class($backend) == 'Magento\Framework\Cache\Backend\RemoteSynchronizedCache') { // phpcs:ignore
            $backend = $this->getPrivateProperty($backend, 'remote');
        }

        if (!$this->isRedisCacheBackend($backend)) {
            return [];
        }

        /** @var \Credis_Client $redis */
        $redis = $this->getPrivateProperty($backend, '_redis');

        $prefix = self::REDIS_KEY_PREFIX . $this->cacheKeyPrefixGenerator->generate() . '_';
        $prefixLength = strlen($prefix);
        $pattern = $prefix . '*';
        $ids = [];
        $iterator = null;

        do {
            $cacheKeys = $redis->scan($iterator, $pattern, self::MAX_AMOUNT_OF_KEYS_PER_SCAN);

            if ($cacheKeys == false) {
                continue;
            }

            $productIds = array_map(function ($key) use ($prefixLength) { // phpcs:ignore
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
     * @throws \ReflectionException
     */
    protected function getPrivateProperty($object, string $propertyName)
    {
        $reflection = new \ReflectionClass($object);

        $property = $reflection->getProperty($propertyName);
        $property->setAccessible(true);

        return $property->getValue($object);
    }

    /**
     * @param \Zend_Cache_Backend_Interface $backend
     * @return bool
     */
    public function isRedisCacheBackend(\Zend_Cache_Backend_Interface $backend): bool
    {
        return ($backend instanceof \Cm_Cache_Backend_Redis);
    }
}
