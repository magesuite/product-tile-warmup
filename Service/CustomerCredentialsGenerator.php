<?php

namespace MageSuite\ProductTileWarmup\Service;

/**
 * This class is final on purpose to guarantee a known, reproducible credentials generation algorithm.
 */
final class CustomerCredentialsGenerator
{
    /**
     * Subdomain name prepended to fake account's email address hostname.
     */
    const CUSTOMER_DOMAIN_PREFIX = 'dummy';

    /**
     * Short informative string rendered as a comment in the e-mail address.
     *
     * Added so one can easily filter out the fake warmup customer accounts later.
     */
    const CUSTOMER_EMAIL_COMMENT = 'warmup';

    /**
     * @var \Magento\Framework\App\DeploymentConfig
     */
    private $deploymentConfig;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    public function __construct(
        \Magento\Framework\App\DeploymentConfig $deploymentConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->deploymentConfig = $deploymentConfig;
        $this->storeManager = $storeManager;
    }

    /**
     * Returns a secret key that is unique for each Magento installation and which is used as salt needed
     * for generating fake account credentials in a predictable, reproducible way.
     *
     * Magento Crypt Key is used for these purpose to avoid having to set up another unique value manually.
     *
     * @return string
     * @throws null No need to handle, as it shall never throw if shop is properly installed.
     */
    private function getInstallationSecretKey(): string
    {
        if (!$key = $this->deploymentConfig->get(\Magento\Framework\Config\ConfigOptionsListConstants::CONFIG_PATH_CRYPT_KEY)) {
            // Make triple-sure that we've got a non-empty crypt key
            throw new \Magento\Framework\Exception\ConfigurationMismatchException(
                new \Magento\Framework\Phrase('Crypt key must be set to a non-empty unique value!')
            );
        }

        return $key;
    }

    /**
     * Returns a hostname uniquely identifying this Magento installation.
     *
     * The default store's base url host is used for this purpose.
     *
     * @throws null No need to handle, as it shall never throw if shop is properly installed.
     */
    private function getInstallationHostname(): string
    {
        return parse_url(
            $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB),
            PHP_URL_HOST
        );
    }

    /**
     * Generates e-mail for fake user belonging to customer group.
     *
     * Example result assuming customer group id 5 and default store's base url is 'https://magesuite.me':
     *  "cg-005+warmup@dummy.magesuite.me"
     *
     * @param int|null $customerGroupId
     * @return string|null
     * @throws null No need to handle, as it shall never throw if shop is properly installed.
     */
    public function getEmail(int $customerGroupId = null): ?string
    {
        if (null === $customerGroupId) {
            return null;
        }

        // Warning: The generated e-mail address must be guaranteed to not exist / be not routable!
        //
        // If the e-mail domain exists and is potentially controlled by some 3rd party a malicious actor
        // may intercept password reset e-mails (e.g. using catch-all) and break into one of the fake accounts.
        //
        // This is especially important if some of the customer groups used for warmup have special privileges
        // like discounted prices, free shipping, etc.
        //
        // Thus it's very important that the e-mail address domain is either:
        //  - Not routable at all.
        //  - Is controlled by a trusted entity (e.g. store operator)

        return sprintf(
            'cg-%03d+%s@%s.%s',
            $customerGroupId,
            self::CUSTOMER_EMAIL_COMMENT,
            self::CUSTOMER_DOMAIN_PREFIX,
            $this->getInstallationHostname()
        );
    }

    /**
     * @param int|null $customerGroupId
     * @return string|null
     * @throws null No need to handle, as it shall never throw if shop is properly installed.
     */
    public function getPassword(int $customerGroupId = null): ?string
    {
        if (null === $customerGroupId) {
            return null;
        }

        return sha1(implode('$$', [
            $customerGroupId,
            $this->getInstallationHostname(),
            $this->getInstallationSecretKey(),
        ]));
    }

    /**
     * Generates fake customer credentials for specified customer group.
     *
     * @param int|null $customerGroupId
     * @return array|null Returns list($email, $password) array or null if no login is necessary (guest).
     */
    public function getCredentials(int $customerGroupId = null): ?array
    {
        if (null === $customerGroupId) {
            return null;
        }
        return [
            $this->getEmail($customerGroupId),
            $this->getPassword($customerGroupId)
        ];
    }
}
