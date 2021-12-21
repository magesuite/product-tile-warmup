<?php

namespace MageSuite\ProductTileWarmup\Service;

class CliWorkerInitializer
{
    public const DEFAULT_GROUP_ID = 0;

    /**
     * @var Config\WorkerConfigGenerator
     */
    protected $workerConfigGenerator;

    /**
     * @var \MageSuite\ProductTileWarmup\Helper\Configuration
     */
    protected $configuration;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\FlagManager
     */
    protected $flagManager;

    /**
     * @var \Magento\Framework\Module\Dir
     */
    protected $moduleDirectory;

    public function __construct(
        \MageSuite\ProductTileWarmup\Service\Config\WorkerConfigGenerator $workerConfigGenerator,
        \MageSuite\ProductTileWarmup\Helper\Configuration $configuration,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\FlagManager $flagManager,
        \Magento\Framework\Module\Dir $moduleDirectory
    )
    {
        $this->workerConfigGenerator = $workerConfigGenerator;
        $this->configuration = $configuration;
        $this->storeManager = $storeManager;
        $this->flagManager = $flagManager;
        $this->moduleDirectory = $moduleDirectory;
    }

    public function initialize()
    {
        $configDirectory = sprintf('%s/var/tile_warmup', BP);

        if (!file_exists($configDirectory) && !is_dir($configDirectory)) {
            @mkdir($configDirectory, 0777, true);
        }

        $configPath = sprintf('%s/worker_config.json', $configDirectory);

        file_put_contents(
            sprintf($configPath, $configDirectory),
            json_encode($this->workerConfigGenerator->getConfigContents())
        );

        $moduleDirectory = $this->moduleDirectory->getDir('MageSuite_ProductTileWarmup');

        foreach ($this->getProcessesGroups() as $groupId => $group) {
            if ($this->workerProcessIsRunning($groupId)) {
                continue;
            }

            $commandPath = sprintf(
                'php %s/Worker/cli warmup:worker',
                $moduleDirectory
            );

            $commandPath .= ' --configuration_file=' . $configPath;
            $commandPath .= ' --group_id=' . $groupId;
            $commandPath .= ' ' . $this->generateStoresArguments($group);
            $commandPath .= ' > /dev/null 2>&1 &';

            echo 'Running ' . $commandPath . PHP_EOL;

            exec($commandPath);
        }
    }

    protected function getProcessesGroups()
    {
        $disabledStoreIds = $this->configuration->getDisabledStoreViewIds();
        $processesConfiguration = $this->configuration->getWorkerProcessesConfiguration();

        $groups = [];

        foreach ($this->storeManager->getStores() as $store) {
            $storeId = $store->getId();

            if (in_array($storeId, $disabledStoreIds)) {
                continue;
            }

            if (!isset($processesConfiguration[$storeId])) {
                $groups[self::DEFAULT_GROUP_ID][] = $storeId;
                continue;
            }

            $processConfiguration = $processesConfiguration[$storeId];

            if (
                isset($processConfiguration['run_in_separate_process_group']) &&
                $processConfiguration['run_in_separate_process_group'] == 0
            ) {
                $groups[self::DEFAULT_GROUP_ID][] = $storeId;
                continue;
            }

            $groups[$processConfiguration['group_id']][] = $storeId;
        }

        return $groups;
    }

    protected function generateStoresArguments($group)
    {
        $arguments = [];

        foreach ($group as $storeId) {
            $arguments[] = sprintf('--store_id=%s', $storeId);
        }

        return implode(' ', $arguments);
    }

    /**
     * @param array $pidsPerGroup
     * @param int $groupId
     * @return bool
     */
    public function workerProcessIsRunning(int $groupId): bool
    {
        exec('ps aux', $processes);

        foreach ($processes as $process) {
            if (
                strpos($process, 'warmup:worker') !== false &&
                strpos($process, 'group_id=' . $groupId) !== false
            ) {
                return true;
            }
        }

        return false;
    }
}
