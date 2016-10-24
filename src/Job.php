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

namespace Cawa\Queue;

use Cawa\Console\Command;
use Cawa\Queue\Exceptions\InvalidException;
use Symfony\Component\Console\Input\ArrayInput;

class Job extends Envelope
{
    /**
     * @param string $class
     * @param array $args
     */
    public function __construct(string $class = null, array $args = [])
    {
        $this->headers['class'] = $class;
        parent::__construct($args);
    }

    /**
     * @return string
     */
    public function getClass() : string
    {
        return $this->headers['class'];
    }

    /**
     * @param string $class
     *
     * @return self|$this
     */
    public function setClass(string $class) : self
    {
        $this->headers['class'] = $class;

        return $this;
    }

    /**
     * @throws InvalidException
     *
     * @return Command
     */
    public function getCommand() : Command
    {
        $class = $this->getClass();

        if (!class_exists($class)) {
            throw new InvalidException(sprintf("Undefined class '%s'", $class));
        }

        $command = new $class();

        if (!$command instanceof Command) {
            throw new InvalidException(sprintf("Invalid class '%s'", $class));
        }

        $input = new ArrayInput($this->getBody(), $command->getDefinition());

        $command->setInput($input)
            ->setStart();

        return $command;
    }
}
