<?php

namespace MageSuite\ProductTileWarmup\Service\Config;

class WorkerConfigGenerator
{
    protected \MageSuite\ProductTileWarmup\Helper\Configuration $configuration;

    protected \Magento\Store\Model\StoreManagerInterface $storeManager;

    protected \Magento\Customer\Api\GroupManagementInterface $groupManagement;

    protected \MageSuite\WarmupCustomerCredentialsGenerator\Service\Credentials\CredentialsProviderLazyCreateDecorator $credentialsProvider;

    public function __construct(
        \MageSuite\ProductTileWarmup\Helper\Configuration $configuration,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Customer\Api\GroupManagementInterface $groupManagement,
        \MageSuite\WarmupCustomerCredentialsGenerator\Service\Credentials\CredentialsProviderLazyCreateDecorator $credentialsProvider
    ) {
        $this->configuration = $configuration;
        $this->storeManager = $storeManager;
        $this->groupManagement = $groupManagement;
        $this->credentialsProvider = $credentialsProvider;
    }

    public function getConfigContents()
    {
        $config = ['stores' => []];

        $config['env_file_path'] = BP . '/app/etc/env.php';
        $config['debug_mode'] = $this->configuration->isDebugModeEnabled();

        if ($this->configuration->getBasicAuthUsername() && $this->configuration->getBasicAuthPassword()) {
            $config['auth'] = [
                'username' => $this->configuration->getBasicAuthUsername(),
                'password' => $this->configuration->getBasicAuthPassword(),
            ];
        }

        foreach ($this->storeManager->getStores() as $store) {
            if (in_array($store->getId(), $this->configuration->getDisabledStoreViewIds())) {
                continue;
            }

            $config['stores'][] = $this->getStoreData($store);
        }

        return $config;
    }

    protected function getStoreData(\Magento\Store\Api\Data\StoreInterface $store)
    {
        return [
            'host' => $this->getHost($store),
            'store_id' => $store->getId(),
            'tile_warmup_url' => $this->getUrl('tile/warmup', $store),
            'is_logged_in_check_url' => $this->getUrl('customer/section/load', $store),
            'login_form_url' => $this->getUrl('customer/account/login', $store),
            'login_url' => $this->getUrl('tile/warmup/loginpost', $store),
            'customer_groups' => $this->getCustomerGroups($store),
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

        foreach ($customerGroups as $customerGroup) {
            if (!in_array($customerGroup->getId(), $this->configuration->getCustomerGroupIds())) {
                continue;
            }

            $group = [
                'customer_group_id' => $customerGroup->getId(),
                'is_guest' => (bool)($customerGroup->getId() === $notLoggedInGroup->getId()),
            ];

            if (!$group['is_guest']) {
                $credentials = $this->credentialsProvider->get($store->getId(), $customerGroup->getId());

                $group['credentials'] = [
                    'login' => $credentials->getUsername(),
                    'password' => $credentials->getPassword(),
                ];
            }

            $results[] = $group;
        }

        return $results;
    }

    protected function getHost(\Magento\Store\Api\Data\StoreInterface $store): ?string
    {
        if (!$this->configuration->isLocalhostModeEnabled()) {
            return null;
        }

        $urlParts = parse_url($store->getBaseUrl()); //phpcs:ignore

        return $urlParts['host'] ?? null;
    }

    protected function getUrl(string $path, \Magento\Store\Api\Data\StoreInterface $store): string
    {
        if ($this->configuration->isLocalhostModeEnabled()) {
            return sprintf('http://127.0.0.1:80/%s', $path);
        }

        return $store->getUrl($path);
    }
}
