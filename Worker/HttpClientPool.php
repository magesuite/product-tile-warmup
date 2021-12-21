<?php

namespace MageSuite\ProductTileWarmup\Worker;

class HttpClientPool
{
    /**
     * @var \GuzzleHttp\Client[]
     */
    protected $clients;

    public function get(int $storeId, int $customerGroupId)
    {
        if (!isset($this->clients[$storeId][$customerGroupId])) {
            $cookieFile = sprintf(
                '%s/var/tile_warmup/cookies_%s_%s.txt',
                BP,
                $storeId,
                $customerGroupId
            );

            $cookieJar = new \GuzzleHttp\Cookie\FileCookieJar($cookieFile, true);

            $this->clients[$storeId][$customerGroupId] = new \GuzzleHttp\Client([
                'cookies' => $cookieJar,
                'timeout' => 60
            ]);
        }

        return $this->clients[$storeId][$customerGroupId];
    }
}
