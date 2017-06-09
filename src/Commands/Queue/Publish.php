<?php

/*
 * This file is part of the Сáша framework.
 *
 * (c) tchiotludo <http://github.com/tchiotludo>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Cawa\Queue\Commands\Queue;

use Cawa\Console\Command;
use Cawa\Queue\QueueFactory;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Publish extends Command
{
    use QueueFactory;

    /**
     *
     */
    protected function configure()
    {
        $this->setName('queue:publish')
            ->setDescription('Publish a message to a queue')
            ->addOption('queue', null, InputOption::VALUE_OPTIONAL, 'Name of a queue to add this job to.', 'default')
            ->addArgument('body', InputArgument::REQUIRED, 'JSON encoded or string that is used for message properties.')
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $body = $input->getArgument('body');
        $json = json_decode($input->getArgument('body'), true);
        if (json_last_error() !== 0) {
            $body = $json;
        }

        $queue = self::queue($input->getOption('queue'));
        $queue->publish($body);

        return 0;
    }
}
