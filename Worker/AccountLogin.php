<?php

namespace MageSuite\ProductTileWarmup\Worker;

class AccountLogin
{
    const DELAY_BETWEEN_LOGIN_CHECKS_IN_MINUTES = 1;

    protected $lastLoginStatusCheck = [];

    /**
     * @var Logger
     */
    protected $logger;

    public function __construct(\MageSuite\ProductTileWarmup\Worker\Logger $logger)
    {

        $this->logger = $logger;
    }

    public function login(\GuzzleHttp\Client $httpClient, $store, $customerGroup)
    {
        if (!$this->enoughTimePassedFromLastCheck(
            $store['store_id'],
            $customerGroup['customer_group_id']
        )
        ) {
            return;
        }

        if ($this->isLoggedIn($httpClient, $store, $customerGroup)) {
            return;
        }

        $this->submitLoginForm($httpClient, $store, $customerGroup);
    }

    protected function enoughTimePassedFromLastCheck($storeId, $customerGroupId): bool
    {
        $lastCheck = $this->lastLoginStatusCheck[$storeId][$customerGroupId] ?? 0;

        return time() >= ($lastCheck + self::DELAY_BETWEEN_LOGIN_CHECKS_IN_MINUTES * 60);
    }

    protected function isLoggedIn($httpClient, $store, $customerGroup)
    {
        $storeId = $store['store_id'];
        $customerGroupId = $customerGroup['customer_group_id'];

        try {
            $isLoggedInResult = $httpClient->get($store['is_logged_in_check_url']);
            $isLoggedInResult = (string)$isLoggedInResult->getBody();
            $isLoggedInResult = json_decode($isLoggedInResult, true);
        } catch (\Exception $e) {
            $this->logger->log('Exception: ' . $e->getMessage());

            if ($e->getCode() == 401) {
                $this->logger->log('Shop returns unauthorized HTTP code, please configure basic auth');
                die; // phpcs:ignore
            }

            return false;
        }

        if (isset($isLoggedInResult['customer']['email']) &&
            $isLoggedInResult['customer']['email'] == $customerGroup['credentials']['login']
        ) {
            $this->lastLoginStatusCheck[$storeId][$customerGroupId] = time();
            return true;
        }

        return false;
    }

    protected function submitLoginForm(\GuzzleHttp\Client $httpClient, $store, $customerGroup): void
    {
        try {
            $postData = [
                'login' => [
                    'username' => $customerGroup['credentials']['login'],
                    'password' => $customerGroup['credentials']['password']
                ]
            ];

            $httpClient->post($store['login_url'], ['form_params' => $postData]);
        } catch (\Exception $e) {
            $this->logger->log('Exception: ' . $e->getMessage());

            if ($e->getCode() == 401) {
                $this->logger->log('Shop returns unauthorized HTTP code, please configure basic auth');
                die; // phpcs:ignore
            }
        }
    }
}
