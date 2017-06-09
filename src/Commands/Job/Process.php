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

use Cawa\Console\Application;
use Cawa\Error\ErrorEvent;
use Cawa\Events\DispatcherFactory;
use Cawa\Queue\Commands\AbstractConsume;
use Cawa\Queue\Envelope;
use Cawa\Queue\Exceptions\FailureException;
use Cawa\Queue\Exceptions\InvalidException;
use Cawa\Queue\Exceptions\ManualRequeueException;
use Cawa\Queue\Job;
use Cawa\Queue\Message;
use Cawa\Queue\QueueFactory;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Debug\Exception\FatalThrowableError;

class Process extends AbstractConsume
{
    use QueueFactory;
    use DispatcherFactory;

    /**
     *
     */
    protected function configure()
    {
        parent::configure();

        $this->setName('job:process')
            ->setDescription('Process job from a queue')
            ->addOption('retry', null, InputOption::VALUE_OPTIONAL, 'Push to failed queued after x retries failed', 3)
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

        $this->output->writeln('job process started');

        $this->queue->consume(Envelope::callback(function (Message $message, Envelope $envelope = null) {
            return $this->consumeCallback($message, $envelope);
        }));

        return $this->sendExitCode();
    }

    /**
     * @param Message $message
     * @param Envelope|Job $envelope
     *
     * @throws FatalThrowableError
     * @throws InvalidException
     * @throws \Throwable
     *
     * @return bool
     */
    protected function consume(Message $message, Envelope $envelope = null) : bool
    {
        if (!$envelope instanceof Job) {
            throw new InvalidException(sprintf("Invalid class '%s'", get_class($envelope)));
        }

        try {
            $job = $envelope->getCommand();

            $options = json_encode($job->getInput()->getOptions());
            $arguments = json_encode($job->getInput()->getArguments());

            $arguments = strlen($arguments) > 50 ? substr($arguments, 0, 50) . '...' : $arguments;
            $options = strlen($options) > 50 ? substr($options, 0, 50) . '...' : $options;

            $this->output->write(sprintf(
                "New job for class '%s' with options '%s' and arguments '%s'",
                $envelope->getClass(),
                $options,
                $arguments
            ), OutputInterface::VERBOSITY_DEBUG);

            $job->setOutput($this->output);

            $return = $job->execute($job->input, $this->output) == 0;

            return $return;
        } catch (\Throwable $exception) {
            if (!$exception instanceof \Exception) {
                $exception = new FatalThrowableError($exception);
            }

            $this->output->writeError(sprintf(
                "%s on job '%s' with message '%s'",
                get_class($exception),
                $envelope->getClass(),
                $exception->getMessage()
            ));

            Application::writeException($exception, $this->output, ConsoleOutputInterface::VERBOSITY_VERBOSE);

            self::emit(new ErrorEvent($exception));

            if ($this->input->getOption('retry') && !$exception instanceof FailureException) {
                if ($this->input->getOption('retry') > count($envelope->getHistory())) {
                    if ($this->input->getOption('stop-on-error')) {
                        $this->exitCode = 1;
                    }

                    $this->queue()->publish($envelope->serialize());
                    throw new ManualRequeueException($this->input->getOption('stop-on-error') === 1);
                } else {
                    $errorMessage = sprintf(
                        "Max retries %s reach for %s on job '%s' with message '%s'",
                        $this->input->getOption('retry'),
                        get_class($exception),
                        $envelope->getClass(),
                        $exception->getMessage()
                    );

                    $this->output->writeError($errorMessage);
                    throw new FailureException($message, $errorMessage, $exception);
                }
            }

            if ($this->input->getOption('stop-on-error') && !$exception instanceof FailureException) {
                $this->exitCode = 1;
                throw $exception;
            }

            return true;
        }
    }
}
