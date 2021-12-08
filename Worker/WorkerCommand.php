<?php

namespace MageSuite\ProductTileWarmup\Worker;

class WorkerCommand extends \Symfony\Component\Console\Command\Command
{
    protected static $defaultName = 'warmup:worker';

    protected function configure(): void
    {
        $this->addOption(
            'configuration_file',
            'c',
            \Symfony\Component\Console\Input\InputOption::VALUE_REQUIRED,
            'Configuration file'
        );

        $this->addOption(
            'store_id',
            's',
            \Symfony\Component\Console\Input\InputOption::VALUE_OPTIONAL | \Symfony\Component\Console\Input\InputOption::VALUE_IS_ARRAY,
            'Store id'
        );
    }

    protected function execute(
        \Symfony\Component\Console\Input\InputInterface $input,
        \Symfony\Component\Console\Output\OutputInterface $output
    ): int
    {
        $workerConfigurationFilePath = $input->getOption('configuration_file');

        if ($workerConfigurationFilePath === null) {
            die('Worker configuration file must be passed');
        }

        if (!file_exists($workerConfigurationFilePath)) {
            die('Worker configuration file does not exist');
        }

        $workerConfiguration = json_decode(file_get_contents($workerConfigurationFilePath), true);

        $requestDelayStatus = new RequestDelayStatus();
        $accountLogin = new AccountLogin();

        $clients = [];

        $resetDelayPath = BP . '/var/tile_warmup/reset_delay';

        while (true) {
            if (file_exists($resetDelayPath)) {
                echo 'Resetting all delays' . PHP_EOL;

                $requestDelayStatus->resetAllDelays();
                unlink($resetDelayPath);
            }

            foreach ($workerConfiguration['stores'] as $store) {
                $storeId = $store['store_id'];

                if (!empty($input->getOption('store_id')) && !in_array($storeId, $input->getOption('store_id'))) {
                    continue;
                }

                foreach ($store['customer_groups'] as $customerGroup) {
                    $customerGroupId = $customerGroup['customer_group_id'];

                    if (!$requestDelayStatus->shouldMakeRequest($storeId, $customerGroupId)) {
                        continue;
                    }

                    if (!isset($clients[$storeId][$customerGroupId])) {
                        $cookieFile = sprintf(
                            '%s/var/tile_warmup/cookies_%s_%s.txt',
                            BP,
                            $storeId,
                            $customerGroupId
                        );

                        $cookieJar = new \GuzzleHttp\Cookie\FileCookieJar($cookieFile, true);

                        $clients[$storeId][$customerGroupId] = new \GuzzleHttp\Client([
                            'cookies' => $cookieJar,
                            'timeout' => 60
                        ]);
                    }

                    $httpClient = $clients[$storeId][$customerGroupId];

                    if ($customerGroup['is_guest'] === false) {
                        $accountLogin->login(
                            $httpClient,
                            $store,
                            $customerGroup
                        );
                    }

                    while (true) {
                        $tileWarmupUrl = $store['tile_warmup_url'];

                        echo sprintf('Warming up: %s for customer group %s %s', $tileWarmupUrl, $customerGroupId, PHP_EOL);

                        $response = $httpClient->request(
                            'HEAD',
                            $tileWarmupUrl,
                            [
                                'headers' => [
                                    'User-Agent' => 'ProductTileWarmup/1.0'
                                ]
                            ]
                        );

                        $warmedUpTilesCount = $response->getHeader('X-Rendered-Tiles-Count')[0] ?? null;
                        $alreadyWarmedTilesCount = $response->getHeader('X-Already-Warmed-Tiles-Count')[0] ?? null;

                        $requestDelayStatus->requestWasMade($storeId, $customerGroupId);

                        echo sprintf('Warmed up %s tiles%s', $warmedUpTilesCount, PHP_EOL);
                        echo sprintf('Already warmed %s tiles%s', $warmedUpTilesCount + $alreadyWarmedTilesCount, PHP_EOL);

                        if ((int)$warmedUpTilesCount === 0) {
                            $requestDelayStatus->delayRequests($storeId, $customerGroupId);

                            echo sprintf(
                                'Delaying next warmup to %d seconds %s',
                                $requestDelayStatus->getDelay($storeId, $customerGroupId),
                                PHP_EOL
                            );

                            break;
                        } else {
                            echo 'Continuing warmup' . PHP_EOL;
                            $requestDelayStatus->resetDelay($storeId, $customerGroupId);
                        }
                    }
                }
            }

            sleep(1);
        }
    }
}
