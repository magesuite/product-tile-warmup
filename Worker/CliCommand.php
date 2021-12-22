<?php

namespace MageSuite\ProductTileWarmup\Worker;

class CliCommand extends \Symfony\Component\Console\Command\Command
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

        $this->addOption(
            'group_id',
            'g',
            \Symfony\Component\Console\Input\InputOption::VALUE_OPTIONAL,
            'Group id'
        );
    }

    protected function execute(
        \Symfony\Component\Console\Input\InputInterface $input,
        \Symfony\Component\Console\Output\OutputInterface $output
    ): int {
        $worker = new Worker($input->getOptions());
        $worker->execute();
    }
}
