<?php

namespace MageSuite\ProductTileWarmup\Command;

class GenerateWorkerConfiguration extends \Symfony\Component\Console\Command\Command
{
    /**
     * @var \MageSuite\ProductTileWarmup\Service\Config\WorkerConfigGeneratorFactory
     */
    protected $workerConfigGeneratorFacotry;

    public function __construct(\MageSuite\ProductTileWarmup\Service\Config\WorkerConfigGeneratorFactory $workerConfigGeneratorFacotry) {
        parent::__construct();

        $this->workerConfigGeneratorFacotry = $workerConfigGeneratorFacotry;
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
        $configGenerator = $this->workerConfigGeneratorFacotry->create();

        $output->write(json_encode($configGenerator->getConfigContents()));
    }
}
