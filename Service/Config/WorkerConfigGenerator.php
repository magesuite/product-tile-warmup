<?php

namespace MageSuite\ProductTileWarmup\Service\Config;

class WorkerConfigGenerator
{
    /**
     * @var \MageSuite\ProductTileWarmup\Helper\Configuration
     */
    protected $configuration;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Customer\Api\GroupManagementInterface
     */
    protected $groupManagement;

    /**
     * @var \MageSuite\WarmupCrawler\Service\Credentials\CredentialsProviderLazyCreateDecorator
     */
    protected $credentialsProvider;

    public function __construct(
        \MageSuite\ProductTileWarmup\Helper\Configuration $configuration,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Customer\Api\GroupManagementInterface $groupManagement,
        \MageSuite\WarmupCrawler\Service\Credentials\CredentialsProviderLazyCreateDecorator $credentialsProvider
    ) {
        $this->configuration = $configuration;
        $this->storeManager = $storeManager;
        $this->groupManagement = $groupManagement;
        $this->credentialsProvider = $credentialsProvider;
    }
    public function getConfigContents() {
        $config = ['stores' => []];

        foreach($this->storeManager->getStores() as $store) {
            if(in_array($store->getId(), $this->configuration->getDisabledStoreViewIds())) {
                continue;
            }

            $config['stores'][] = $this->getStoreData($store);
        }

        return $config;
    }

    protected function getStoreData(\Magento\Store\Api\Data\StoreInterface $store)
    {
        return [
            'store_id' => $store->getId(),
            'customer_groups' => $this->getCustomerGroups($store)
        ];
    }

    protected function getCustomerGroups(\Magento\Store\Api\Data\StoreInterface $store)
    {
        $results = [];

        $notLoggedInGroup = $this->groupManagement->getNotLoggedInGroup();

        $customerGroups = array_merge(
            [$notLoggedInGroup],
            $this->groupManagement->getLoggedInGroups()
        );

        foreach($customerGroups as $customerGroup) {
            $group = [
                'customer_group_id' => $customerGroup->getId(),
                'is_guest' => (bool)($customerGroup->getId() === $notLoggedInGroup->getId()),
            ];

            if(!$group['is_guest']) {
                $this->credentialsProvider->get($store->getId(), $customerGroup->getId());
            }

            $results[] = $group;
        }

        return $results;
    }
}
