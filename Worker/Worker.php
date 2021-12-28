<?php

namespace MageSuite\ProductTileWarmup\Worker;

class Worker
{
    /**
     * @var string
     */
    protected $workerConfigurationFilePath;

    /**
     * @var array
     */
    protected $workerConfiguration;

    /**
     * @var array
     */
    protected $databaseConnection;

    /**
     * @var RequestDelayStatus
     */
    protected $requestDelayStatus;

    /**
     * @var AccountLogin
     */
    protected $accountLogin;

    /**
     * @var ResetChecker
     */
    protected $resetChecker;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var \Symfony\Component\Stopwatch\Stopwatch
     */
    protected $stopwatch;

    /**
     * @var array
     */
    protected $options;

    /**
     * @var HttpClientPool
     */
    protected $httpClientsPool;

    public function __construct($options)
    {
        $this->options = $options;
        $this->workerConfigurationFilePath = $this->options['configuration_file'];
        $this->workerConfiguration = json_decode(file_get_contents($this->workerConfigurationFilePath), true); // phpcs:ignore
        $this->databaseConnection = new DatabaseConnection($this->workerConfiguration['env_file_path']);
        $this->requestDelayStatus = new RequestDelayStatus();
        $this->accountLogin = new AccountLogin();
        $this->resetChecker = new ResetChecker($this->databaseConnection);
        $this->logger = new Logger($this->workerConfiguration['debug_mode'] ?? false);
        $this->stopwatch = new \Symfony\Component\Stopwatch\Stopwatch();
        $this->httpClientsPool = new HttpClientPool();
    }

    public function execute()
    {
        while (true) {
            try {
                $this->resetChecker->check();
                $this->warmupStores();

                sleep(1); // phpcs:ignore
            } catch (ResetException $e) {
                $this->logger->log('Resetting delays');

                $this->requestDelayStatus->resetAllDelays();
                $this->resetChecker->markResetAsDone();
            }
        }
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     */
    protected function warmupStores(): void
    {
        foreach ($this->workerConfiguration['stores'] as $store) {
            $storeId = $store['store_id'];

            if (!empty($this->options['store_id']) && !in_array($storeId, $this->options['store_id'])) {
                continue;
            }

            $this->warmupCustomerGroups($store);
        }
    }

    /**
     * @param $store
     * @param $storeId
     */
    protected function warmupCustomerGroups($store): void
    {
        $storeId = $store['store_id'];

        foreach ($store['customer_groups'] as $customerGroup) {
            $customerGroupId = $customerGroup['customer_group_id'];

            if (!$this->requestDelayStatus->shouldMakeRequest($storeId, $customerGroupId)) {
                continue;
            }

            $httpClient = $this->httpClientsPool->get($storeId, $customerGroupId);

            if ($customerGroup['is_guest'] === false) {
                try {
                    $this->accountLogin->login(
                        $httpClient,
                        $store,
                        $customerGroup
                    );
                } catch (\Exception $e) {
                    continue;
                }
            }

            $this->warmTiles($store, $customerGroupId);
        }
    }

    /**
     * @param $store
     * @param $customerGroupId
     * @param $httpClient
     */
    protected function warmTiles($store, $customerGroupId): void
    {
        $storeId = $store['store_id'];
        $httpClient = $this->httpClientsPool->get($storeId, $customerGroupId);

        while (true) {
            $this->resetChecker->check();

            $tileWarmupUrl = $store['tile_warmup_url'];

            $this->logger->log(sprintf('Warming up: %s for customer group %s', $tileWarmupUrl, $customerGroupId));

            $this->stopwatch->reset();
            $this->stopwatch->start('tile_warmup_request');

            try {
                $options = ['headers' => ['User-Agent' => 'ProductTileWarmup/1.0']];

                $response = $httpClient->request(
                    'HEAD',
                    $tileWarmupUrl,
                    $options
                );
            } catch (\Exception $e) {
                $this->logger->log('Exception: ' . $e->getMessage());
                continue;
            }

            $elapsed = number_format($this->stopwatch->stop('tile_warmup_request')->getDuration()/1000, 2);

            $warmedUpTilesCount = $response->getHeader('X-Rendered-Tiles-Count')[0] ?? null;
            $alreadyWarmedTilesCount = $response->getHeader('X-Already-Warmed-Tiles-Count')[0] ?? null;

            $this->requestDelayStatus->requestWasMade($storeId, $customerGroupId);

            $this->logger->log(sprintf('Warmed up %s tiles, took %ss', $warmedUpTilesCount, $elapsed));
            $this->logger->log(sprintf('Already warmed %s tiles', $warmedUpTilesCount + $alreadyWarmedTilesCount));

            if ((int)$warmedUpTilesCount === 0 || $warmedUpTilesCount < 100) {
                $this->requestDelayStatus->delayRequests($storeId, $customerGroupId);

                $this->logger->log(sprintf(
                    'Delaying next warmup to %d seconds',
                    $this->requestDelayStatus->getDelay($storeId, $customerGroupId),
                ));

                return;
            }

            $this->requestDelayStatus->resetDelay($storeId, $customerGroupId);
        }
    }
}
