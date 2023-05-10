<?php

namespace MageSuite\ProductTileWarmup\Command;

class GenerateWorkerConfiguration extends \Symfony\Component\Console\Command\Command
{
    protected \MageSuite\ProductTileWarmup\Service\Config\WorkerConfigGeneratorFactory $workerConfigGeneratorFactory;

    public function __construct(\MageSuite\ProductTileWarmup\Service\Config\WorkerConfigGeneratorFactory $workerConfigGeneratorFactory)
    {
        parent::__construct();

        $this->workerConfigGeneratorFactory = $workerConfigGeneratorFactory;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('cache:warmup:generate-tile-warmup-worker-configuration')
            ->setDescription('Generates tile warmup worker configuration');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(
        \Symfony\Component\Console\Input\InputInterface $input,
        \Symfony\Component\Console\Output\OutputInterface $output
    ) {
        /** @var \MageSuite\ProductTileWarmup\Service\Config\WorkerConfigGenerator $configGenerator */
        $configGenerator = $this->workerConfigGeneratorFactory->create();

        $output->write(json_encode($configGenerator->getConfigContents()));
    }
}
