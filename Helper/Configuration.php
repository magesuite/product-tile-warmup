<?php

namespace MageSuite\ProductTileWarmup\Helper;

class Configuration
{
    const XML_PATH_GENERAL_CRAWLING_ENABLED = 'product_tile_warmup/general/crawling_enabled';
    const XML_PATH_GENERAL_LOCALHOST_MODE_ENABLED = 'product_tile_warmup/general/localhost_mode_enabled';
    const XML_PATH_GENERAL_DEBUG_MODE_ENABLED = 'product_tile_warmup/general/debug_mode_enabled';
    const XML_PATH_GENERAL_PRODUCT_LIMIT = 'product_tile_warmup/general/product_limit';
    const XML_PATH_GENERAL_CUSTOMER_GROUPS = 'product_tile_warmup/general/customer_groups';
    const XML_PATH_GENERAL_DISABLED_STORE_VIEWS = 'product_tile_warmup/general/disabled_store_views';
    const XML_PATH_GENERAL_WORKER_PROCESSES_CONFIGURATION = 'product_tile_warmup/general/worker_processes_configuration';

    const XML_PATH_BASIC_AUTH_USERNAME = 'product_tile_warmup/basic_auth/username';
    const XML_PATH_BASIC_AUTH_PASSWORD = 'product_tile_warmup/basic_auth/password';

    protected \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig;

    protected \Magento\Framework\Serialize\SerializerInterface $serializer;

    protected \Magento\Framework\Encryption\EncryptorInterface $encryptor;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Serialize\SerializerInterface $serializer,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->serializer = $serializer;
        $this->encryptor = $encryptor;
    }

    public function isEnabled(): bool
    {
        return (bool)$this->scopeConfig->getValue(self::XML_PATH_GENERAL_CRAWLING_ENABLED);
    }

    public function isLocalhostModeEnabled(): bool
    {
        return (bool)$this->scopeConfig->getValue(self::XML_PATH_GENERAL_LOCALHOST_MODE_ENABLED);
    }

    public function isDebugModeEnabled(): bool
    {
        return (bool)$this->scopeConfig->getValue(self::XML_PATH_GENERAL_DEBUG_MODE_ENABLED);
    }

    public function getProductLimit(): int
    {
        return (int)$this->scopeConfig->getValue(self::XML_PATH_GENERAL_PRODUCT_LIMIT);
    }

    public function getDisabledStoreViewIds(): array
    {
        $value = $this->scopeConfig->getValue(self::XML_PATH_GENERAL_DISABLED_STORE_VIEWS);

        if ($value === null) {
            return [];
        }

        return explode(',', $value);
    }

    public function getCustomerGroupIds(): array
    {
        $value = $this->scopeConfig->getValue(self::XML_PATH_GENERAL_CUSTOMER_GROUPS);

        if ($value === null) {
            return [];
        }

        return explode(',', $value);
    }

    public function getWorkerProcessesConfiguration(): array
    {
        $value = $this->scopeConfig->getValue(self::XML_PATH_GENERAL_WORKER_PROCESSES_CONFIGURATION);

        if ($value === null) {
            return [];
        }

        return $this->serializer->unserialize($value);
    }

    public function getBasicAuthUsername(): ?string
    {
        return $this->scopeConfig->getValue(self::XML_PATH_BASIC_AUTH_USERNAME);
    }

    public function getBasicAuthPassword(): ?string
    {
        $value = $this->scopeConfig->getValue(self::XML_PATH_BASIC_AUTH_PASSWORD);

        if (!empty($value)) {
            return $this->encryptor->decrypt($value);
        }

        return null;
    }
}
