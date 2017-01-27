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

namespace Cawa\Queue\Drivers;

abstract class AbstractDriver
{
    /**
     * @var array
     */
    protected $options = [];

    /**
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->options = $config;

        $this->connect();
    }

    /**
     * @var string
     */
    protected $workerId;

    /**
     * @return string
     */
    public function getWorkerId() : string
    {
        return $this->workerId;
    }

    /**
     * Connect to a queue.
     *
     * @return bool
     */
    abstract public function connect() : bool;

    /**
     * Disconnect from a queue.
     *
     * @return bool
     */
    abstract public function disconnect() : bool;

    /**
     * Returns a list of all queue names.
     *
     * @return array
     */
    abstract public function list() : array;

    /**
     * Create a queue.
     *
     * @param string $name
     *
     * @return bool
     */
    abstract public function create($name) : bool;

    /**
     * Removes the queue.
     *
     * @param string $name
     *
     * @return bool
     */
    abstract public function remove($name) : bool;

    /**
     * Clear the queue.
     *
     * @param string $name
     *
     * @return bool
     */
    abstract public function clear($name) : bool;

    /**
     * Insert a message at the top of the queue.
     *
     * @param string $name
     * @param string $payload
     *
     * @return bool
     */
    abstract public function publish(string $name, string $payload) : bool;

    /**
     * Remove the next message in line.
     * And if no message is available wait $duration seconds.
     *
     * @param string $name
     * @param callable $callback
     * @param callable $quit
     *
     * @return int
     */
    abstract public function consume(string $name, callable $callback, callable $quit = null) : int;
}
