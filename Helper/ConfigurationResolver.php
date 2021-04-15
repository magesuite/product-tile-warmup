<?php

namespace MageSuite\ProductTileWarmup\Helper;

use Magento\Framework\Exception\NoSuchEntityException;
use MageSuite\ProductTileWarmup\Helper\Configuration;

class ConfigurationResolver extends Configuration
{
    /**
     * @var \Magento\Framework\UrlInterface[]|array
     */
    private $storeUrlGenerators = [];

    /**
     * @var \Magento\Framework\App\Cache\Manager
     */
    protected $cacheManager;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\UrlFactory
     */
    protected $urlFactory;

    /**
     * @var \MageSuite\ProductTileWarmup\Service\CustomerCredentialsGenerator
     */
    private $credentialsGenerator;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\App\Cache\Manager $cacheManager,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\UrlFactory $urlFactory,
        \MageSuite\ProductTileWarmup\Service\CustomerCredentialsGenerator $credentialsGenerator
    ) {
        parent::__construct($scopeConfig);

        $this->cacheManager = $cacheManager;
        $this->storeManager = $storeManager;
        $this->urlFactory = $urlFactory;
        $this->credentialsGenerator = $credentialsGenerator;
    }

    /**
     * @param null|string|bool|int|\Magento\Store\Api\Data\StoreInterface $storeId
     * @return \Magento\Framework\UrlInterface
     */
    protected function getScopedUrlGenerator($storeId): \Magento\Framework\UrlInterface
    {
        if (isset($this->storeUrlGenerators[$storeId])) {
            return $this->storeUrlGenerators[$storeId];
        }

        $urlGenerator = $this->urlFactory->create();
        $urlGenerator->setScope($storeId);

        return $this->storeUrlGenerators[$storeId] = $urlGenerator;
    }

    /**
     * @param null|string|bool|int|\Magento\Store\Api\Data\StoreInterface $storeId
     * @return string|null
     */
    public function getStoreViewBaseUrl($storeId): ?string
    {
        try {
            return $this
                ->getScopedUrlGenerator($storeId)
                ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB);
        } catch (NoSuchEntityException $exception) {
            return null;
        }
    }

    /**
     * @param null|string|bool|int|\Magento\Store\Api\Data\StoreInterface $storeId
     * @param string|null $routePath
     * @param array|null $routeParams
     * @return string|null
     */
    public function getRouteUrl($storeId, string $routePath = null, array $routeParams = null): ?string
    {
        try {
            return $this->getScopedUrlGenerator($storeId)->getUrl($routePath, $routeParams);
        } catch (NoSuchEntityException $exception) {
            return null;
        }
    }

    /**
     * @param int|null $customerGroupId
     * @return array|null Returns list($username, $password) array or null if no login.
     */
    public function getCustomerCredentials(int $customerGroupId = null): ?array
    {
        if (null === $customerGroupId || static::CUSTOMER_GROUP_GUEST_ID === $customerGroupId) {
            return null;
        }


    }
}
