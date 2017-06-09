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

namespace Cawa\Queue\Commands\Job;

use Cawa\Console\Command;
use Cawa\Console\UserException;
use Cawa\Queue\Job;
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
        $this->setName('job:publish')
            ->setDescription('Publish a message to a queue')
            ->addOption('queue', null, InputOption::VALUE_OPTIONAL, 'Name of a queue to add this job to.', 'default')
            ->addArgument('class', InputArgument::REQUIRED, 'Class for the job.')
            ->addArgument('args', InputArgument::REQUIRED, 'JSON encoded string that is used for args.')
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @throws UserException
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $queue = self::queue($input->getOption('queue'));

        $json = json_decode($input->getArgument('args'), true);
        if (json_last_error() !== 0) {
            throw new UserException(sprintf("Invalid json for args with error '%s'", json_last_error_msg()));
        }

        if (stripos($input->getArgument('class'), '\\\\') !== false) {
            throw new UserException(sprintf("Invalid class name '%s'", $input->getArgument('class')));
        }

        $job = new Job($input->getArgument('class'), $json);

        $queue->publish($job->serialize());

        return 0;
    }
}
