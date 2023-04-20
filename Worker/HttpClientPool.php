<?php

namespace MageSuite\ProductTileWarmup\Worker;

class HttpClientPool
{
    /**
     * @var array|null
     */
    protected $auth;

    public function __construct($auth = null)
    {
        $this->auth = $auth;
    }

    /**
     * @var \GuzzleHttp\Client[]
     */
    protected $clients;

    public function get(int $storeId, int $customerGroupId, string $host = '')
    {
        if (!isset($this->clients[$storeId][$customerGroupId])) {
            $cookieFile = sprintf(
                '%s/var/tile_warmup/cookies_%s_%s.txt',
                BP,
                $storeId,
                $customerGroupId
            );

            $cookieJar = new \GuzzleHttp\Cookie\FileCookieJar($cookieFile, true);

            $defaults = [
                'headers' => [
                    'User-Agent' => 'ProductTileWarmup/1.0'
                ]
            ];

            if ($host) {
                $defaults['headers']['Host'] = $host;
            }

            if (isset($this->auth['username'], $this->auth['password']) && !empty($this->auth['username']) && !empty($this->auth['password'])) {
                $defaults['auth'] = [$this->auth['username'], $this->auth['password']];
            }

            $config = array_merge(['cookies' => $cookieJar, 'timeout' => 60], $defaults);
            $this->clients[$storeId][$customerGroupId] = new \GuzzleHttp\Client($config);
        }

        return $this->clients[$storeId][$customerGroupId];
    }
}
