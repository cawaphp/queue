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

namespace Cawa\Queue\Commands\Job;

use Cawa\Console\Application;
use Cawa\Console\Command;
use Cawa\Console\ConsoleOutput;
use Cawa\Queue\Exceptions\AbstractException;
use Cawa\Queue\Commands\AbstractConsume;
use Cawa\Queue\Exceptions\FailureException;
use Cawa\Queue\Exceptions\InvalidException;
use Cawa\Queue\Job;
use Cawa\Queue\QueueFactory;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Process extends AbstractConsume
{
    use QueueFactory;

    /**
     *
     */
    protected function configure()
    {
        parent::configure();

        $this->setName('job:process')
            ->setDescription('Process job from a queue')
            ->addOption('stop-on-error', null, InputOption::VALUE_OPTIONAL, 'Stop consumer when an error occurs.', 1)
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
        parent::execute($input, $output);

        $this->queue->enqueue(function(callable $quit, $envelope = null)
        {
            return $this->consumeCallback($quit, $envelope);
        });

        return 0;
    }

    /**
     * @param Job $envelope
     *
     * @return bool
     * @throws InvalidException
     */
    protected function consume($envelope) : bool
    {
        if (!$envelope instanceof Job) {
            throw new InvalidException(sprintf("Invalid class '%s'", get_class($envelope)));
        }

        $class = $envelope->getClass();

        if (!class_exists($class)) {
            throw new InvalidException(sprintf("Undefined class '%s'", $class));
        }

        $this->output->writeln('New job for class : ' . $class, OutputInterface::VERBOSITY_DEBUG);

        $job = new $class();

        if (!$job instanceof Command) {
            throw new InvalidException(sprintf("Invalid class '%s'", $class));
        }

        $input = new ArrayInput($envelope->getBody(), $job->getDefinition());
        $job->setInput($input)
            ->setOutput($this->output)
        ;

        try {
            $return = $job->execute($input, $this->output) == 0;
            return $return;
        } catch (FailureException $exception) {
            $this->output->writeError(sprintf(
                "FailureException on job '%s' with message '%s'",
                $class,
                $exception->getMessage()
            ));

            Application::writeException($exception, $this->output, ConsoleOutputInterface::VERBOSITY_VERBOSE);

            return true;
        }

    }
}
