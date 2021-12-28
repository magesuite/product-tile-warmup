<?php

namespace MageSuite\ProductTileWarmup\Worker;

class RequestDelayStatus
{
    const DELAY_INCREMENT_IN_SECONDS = 10;
    const MAXIMUM_DELAY_IN_SECONDS = 60*5;

    public $delayStatus = [];

    public function shouldMakeRequest($storeId, $customerGroupId)
    {
        if (!isset($this->delayStatus[$storeId][$customerGroupId])) {
            return true;
        }

        if (!isset($this->delayStatus[$storeId][$customerGroupId]['last_request_timestamp'])) {
            return true;
        }

        if (!isset($this->delayStatus[$storeId][$customerGroupId]['delay'])) {
            return true;
        }

        return time() > $this->delayStatus[$storeId][$customerGroupId]['last_request_timestamp'] + $this->delayStatus[$storeId][$customerGroupId]['delay'];
    }

    public function delayRequests($storeId, $customerGroupId)
    {
        if (!isset($this->delayStatus[$storeId][$customerGroupId]['delay'])) {
            $this->delayStatus[$storeId][$customerGroupId]['delay'] = self::DELAY_INCREMENT_IN_SECONDS;
        }

        if ($this->delayStatus[$storeId][$customerGroupId]['delay'] >= self::MAXIMUM_DELAY_IN_SECONDS) {
            return;
        }

        $this->delayStatus[$storeId][$customerGroupId]['delay'] += self::DELAY_INCREMENT_IN_SECONDS;
    }

    public function resetDelay($storeId, $customerGroupId)
    {
        $this->delayStatus[$storeId][$customerGroupId]['delay'] = 0;
    }

    public function getDelay($storeId, $customerGroupId)
    {
        return $this->delayStatus[$storeId][$customerGroupId]['delay'] ?? null;
    }

    public function requestWasMade($storeId, $customerGroupId)
    {
        $this->delayStatus[$storeId][$customerGroupId]['last_request_timestamp'] = time();
    }

    public function resetAllDelays()
    {
        $this->delayStatus = [];
    }
}
