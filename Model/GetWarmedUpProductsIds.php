<?php

namespace MageSuite\ProductTileWarmup\Model;

class GetWarmedUpProductsIds
{
    const REDIS_KEY_PREFIX = 'zc:k:???_';
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

        if(get_class($backend) == 'Magento\Framework\Cache\Backend\RemoteSynchronizedCache') {
            $backend = $this->getPrivateProperty($backend, 'remote');
        }

        if(!$this->isRedisCacheBackend($backend)) {
            return [];
        }

        /** @var \Credis_Client $redis */
        $redis = $this->getPrivateProperty($backend, '_redis');

        $prefix = self::REDIS_KEY_PREFIX . $this->cacheKeyPrefixGenerator->generate().'_';
        $prefixLength = strlen($prefix);
        $pattern = $prefix .'*';
        $ids = [];
        $iterator = null;

        do {
            $cacheKeys = $redis->scan($iterator, $pattern, self::MAX_AMOUNT_OF_KEYS_PER_SCAN);

            if($cacheKeys == false) {
                break;
            }

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
    protected function getPrivateProperty($object, $propertyName)
    {
        $reflection = new \ReflectionClass($object);

        $property = $reflection->getProperty($propertyName);
        $property->setAccessible(true);

        return $property->getValue($object);
    }

    public function isRedisCacheBackend($backend)
    {
        return ($backend instanceof \Cm_Cache_Backend_Redis);
    }
}
