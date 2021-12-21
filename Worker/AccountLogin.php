<?php

namespace MageSuite\ProductTileWarmup\Worker;

class AccountLogin
{
    const DELAY_BETWEEN_LOGIN_CHECKS_IN_MINUTES = 1;

    protected $lastLoginStatusCheck = [];

    public function login(\GuzzleHttp\Client $httpClient, $store, $customerGroup)
    {
        if (!$this->enoughTimePassedFromLastCheck(
            $store['store_id'],
            $customerGroup['customer_group_id'])
        ) {
            return;
        }

        if($this->isLoggedIn($httpClient, $store, $customerGroup)) {
            return;
        }

        $this->submitLoginForm($httpClient, $store, $customerGroup);
    }

    protected function enoughTimePassedFromLastCheck($storeId, $customerGroupId): bool
    {
        $lastCheck = $this->lastLoginStatusCheck[$storeId][$customerGroupId] ?? 0;

        return time() >= ($lastCheck + self::DELAY_BETWEEN_LOGIN_CHECKS_IN_MINUTES * 60);
    }

    protected function isLoggedIn($httpClient,  $store, $customerGroup) {
        $storeId = $store['store_id'];
        $customerGroupId = $customerGroup['customer_group_id'];

        $isLoggedInResult = $httpClient->get($store['is_logged_in_check_url']);
        $isLoggedInResult = (string)$isLoggedInResult->getBody();
        $isLoggedInResult = json_decode($isLoggedInResult, true);

        if (
            isset($isLoggedInResult['customer']['email']) &&
            $isLoggedInResult['customer']['email'] == $customerGroup['credentials']['login']
        ) {
            $this->lastLoginStatusCheck[$storeId][$customerGroupId] = time();
            return true;
        }

        return false;
    }

    /**
     * @param \GuzzleHttp\Client $httpClient
     * @param $loginFormUrl
     * @param $loginUrl
     * @param $login
     * @param $password
     */
    protected function submitLoginForm(\GuzzleHttp\Client $httpClient, $store, $customerGroup): void
    {
        $loginFormPage = $httpClient->get($store['login_form_url']);
        $html = (string)$loginFormPage->getBody();

        $formKey = $this->getFormKey($html);

        $httpClient->post($store['login_url'],
            [
                'form_params' => [
                    'form_key' => $formKey,
                    'login' => [
                        'username' => $customerGroup['credentials']['login'],
                        'password' => $customerGroup['credentials']['password']
                    ],
                    'send' => '',
                    'persistent_remember_me' => 'on'
                ]
            ]
        );
    }

    /**
     * @param string $html
     */
    public function getFormKey(string $html): ?string
    {
        $dom = new \DOMDocument();
        @$dom->loadHTML($html);

        $xpath = new \DOMXpath($dom);
        $elements = $xpath->query("//form[@id='login-form']//input[@name='form_key']");

        if ($elements->length < 1) {
            return null;
        }

        return $elements->item(0)->getAttribute('value');
    }
}

