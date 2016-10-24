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

class Message
{

    /**
     * @param callable $quit
     * @param string $workerId
     */
    public function __construct(callable $quit, string $workerId)
    {
        $this->quit = $quit;
        $this->workerId = $workerId;
    }

    /**
     * @var callable
     *
     */
    private $quit;

    /**
     * @param bool $quit
     */
    public function quit(bool $quit)
    {
        call_user_func($this->quit, $quit);
    }

    /**
     * @var string
     */
    private $message;

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param string $message
     *
     * @return $this|self
     */
    public function setMessage(string $message) : self
    {
        $this->message = $message;

        return $this;
    }

    /**
     * @var string
     */
    private $workerId;

    /**
     * @return string
     */
    public function getWorkerId() : string
    {
        return $this->workerId;
    }
}
