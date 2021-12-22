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

    /**
     * @var \Magento\Framework\Shell
     */
    protected $shell;

    /**
     * @var \Magento\Framework\Serialize\SerializerInterface
     */
    protected $serializer;

    /**
     * @var \Magento\Framework\Filesystem\Driver\File
     */
    protected $file;

    public function __construct(
        \MageSuite\ProductTileWarmup\Service\Config\WorkerConfigGenerator $workerConfigGenerator,
        \MageSuite\ProductTileWarmup\Helper\Configuration $configuration,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\FlagManager $flagManager,
        \Magento\Framework\Module\Dir $moduleDirectory,
        \Magento\Framework\Shell $shell,
        \Magento\Framework\Serialize\SerializerInterface $serializer,
        \Magento\Framework\Filesystem\Driver\File $file
    ) {
        $this->workerConfigGenerator = $workerConfigGenerator;
        $this->configuration = $configuration;
        $this->storeManager = $storeManager;
        $this->flagManager = $flagManager;
        $this->moduleDirectory = $moduleDirectory;
        $this->shell = $shell;
        $this->serializer = $serializer;
        $this->file = $file;
    }

    public function initialize()
    {
        $configDirectory = sprintf('%s/var/tile_warmup', BP);

        $this->file->createDirectory($configDirectory);

        $configPath = sprintf('%s/worker_config.json', $configDirectory);

        $this->file->filePutContents(
            sprintf($configPath, $configDirectory),
            $this->serializer->serialize($this->workerConfigGenerator->getConfigContents())
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

            $this->shell->execute($commandPath);
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

            if (isset($processConfiguration['run_in_separate_process_group']) &&
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
        $processes = explode(PHP_EOL, $this->shell->execute('ps aux'));

        foreach ($processes as $process) {
            if (strpos($process, 'warmup:worker') !== false &&
                strpos($process, 'group_id=' . $groupId) !== false
            ) {
                return true;
            }
        }

        return false;
    }
}
