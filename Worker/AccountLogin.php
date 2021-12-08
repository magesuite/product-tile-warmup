<?php

namespace MageSuite\ProductTileWarmup\Worker;

class AccountLogin
{
    const DELAY_BETWEEN_LOGIN_CHECKS_IN_MINUTES = 10;

    protected $lastLoginStatusCheck = [];

    public function login(\GuzzleHttp\Client $httpClient, $store, $customerGroup)
    {
        $storeId = $store['store_id'];
        $customerGroupId = $customerGroup['customer_group_id'];

        if (
            isset($this->lastLoginStatusCheck[$storeId][$customerGroupId]) &&
            time() <= ($this->lastLoginStatusCheck[$storeId][$customerGroupId] + self::DELAY_BETWEEN_LOGIN_CHECKS_IN_MINUTES*60)
        ) {
            // we want to check if we are logged only every once X minutes
            return;
        }

        $isLoggedInCheckUrl = $store['is_logged_in_check_url'];
        $loginFormUrl = $store['login_form_url'];
        $loginUrl = $store['login_url'];
        $login = $customerGroup['credentials']['login'];
        $password = $customerGroup['credentials']['password'];


        $isLoggedIn = $httpClient->get($isLoggedInCheckUrl);
        $isLoggedInResult = (string)$isLoggedIn->getBody();
        $isLoggedInResult = json_decode($isLoggedInResult, true);

        $this->lastLoginStatusCheck[$storeId][$customerGroupId] = time();

        if (isset($isLoggedInResult['customer']['email']) && $isLoggedInResult['customer']['email'] == $login) {
            return;
        }

        $result = $httpClient->get($loginFormUrl);
        $html = (string)$result->getBody();

        $formKey = $this->getFormKey($html);

        $httpClient->post($loginUrl,
            [
                'form_params' => [
                    'form_key' => $formKey,
                    'login' => [
                        'username' => $login,
                        'password' => $password
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

