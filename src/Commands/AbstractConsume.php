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

namespace Cawa\Queue\Commands;

use Cawa\Console\Command;
use Cawa\Console\ConsoleOutput;
use Cawa\Console\UserException;
use Cawa\Queue\Drivers\CountableInterface;
use Cawa\Queue\Envelope;
use Cawa\Queue\Queue;
use Cawa\Queue\QueueFactory;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractConsume extends Command
{
    use QueueFactory;

    /**
     *
     */
    protected function configure()
    {
        $this
            ->addOption('queue', null, InputArgument::OPTIONAL, 'Names of one queue that will be consumed.', 'default')
            ->addOption('max-runtime', null, InputOption::VALUE_OPTIONAL, 'Maximum time in seconds the consumer will run.', null)
            ->addOption('max-messages', null, InputOption::VALUE_OPTIONAL, 'Maximum number of messages that should be consumed.', null)
            ->addOption('stop-when-empty', null, InputOption::VALUE_NONE, 'Stop consumer when queue is empty.', null)
        ;
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
        /** @var ConsoleOutput $output */
        $output->setPrefix(ConsoleOutput::PREFIX_TIMESTAMP);

        $this->start = time();
        $this->queue = self::queue($input->getOption('queue'));

        if ($this->input->getOption('stop-when-empty') && !$this->queue->getStorage() instanceof CountableInterface) {
            throw new UserException("Can't use stop-when-empty on '" . $this->queue->getType() . "' storage");
        }

        return 0;
    }

    /**
     * @param callable $quit
     * @param Envelope|string|null $envelope
     *
     * @return bool
     */
    protected function consumeCallback(callable $quit, $envelope = null) : bool
    {
        if ($envelope) {
            $this->count++;
        }

        if ($envelope) {
            return $this->consume($envelope);
        }

        if ($this->mustQuit()) {
            $quit(true);
        }

        return true;
    }

    /**
     * @return bool
     */
    protected function mustQuit() : bool
    {
        if ($this->input->getOption('max-messages') && $this->count >= (int) $this->input->getOption('max-messages')) {
            return true;
        }

        if ($this->input->getOption('stop-when-empty') && $this->queue->count() == 0) {
            return true;
        }

        if ($this->input->getOption('max-runtime') && $this->start + $this->input->getOption('max-runtime') >= time()) {
            return true;
        }

        return false;
    }

    /**
     * @param string|Envelope|null $message
     *
     * @return bool
     */
    abstract protected function consume($message) : bool;
}
