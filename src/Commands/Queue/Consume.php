<?php

/*
 * This file is part of the Сáша framework.
 *
 * (c) tchiotludo <http://github.com/tchiotludo>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare (strict_types = 1);

namespace Cawa\Queue\Commands\Queue;

use Cawa\Console\UserException;
use Cawa\Queue\Commands\AbstractConsume;
use Cawa\Queue\Envelope;
use Cawa\Queue\Message;
use Cawa\Queue\Queue;
use Cawa\Queue\QueueFactory;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Consume extends AbstractConsume
{
    use QueueFactory;

    /**
     *
     */
    protected function configure()
    {
        $this->setName('queue:consume')
            ->setDescription('Consume a queue')
        ;

        parent::configure();
    }

    /**
     * @var Queue
     */
    protected $queue;

    /**
     * @var int
     */
    protected $count = 0;

    /**
     * @var int
     */
    protected $start;

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
        parent::execute($input, $output);

        $this->queue->consume(function(Message $message)
        {
            return $this->consumeCallback($message);
        });

        return $this->sendExitCode();
    }

    /**
     * {@inheritdoc}
     */
    protected function consume(Message $message, Envelope $envelope = null) : bool
    {
        $this->output->writeln('new message: ' . $message->getMessage());

        return true;
    }
}
