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

use Cawa\Queue\Exceptions\FailureException;

class Redis extends AbstractDriver implements CountableInterface
{
    /**
     * @var \Redis
     */
    protected $client;

    /**
     * {@inheritdoc}
     */
    public function connect() : bool
    {
        $this->client = new \Redis();
        $this->client->pconnect(
            $this->options['host'],
            $this->options['port'] ?? 6379,
            $this->options['connectTimeout'] ?? 2.5
        );

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function disconnect() : bool
    {
        $this->client->close();

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function list() : array
    {
        return $this->client->sMembers('queues');
    }

    /**
     * {@inheritdoc}
     */
    public function create($name) : bool
    {
        return $this->client->sAdd('queues', $name) > 0;
    }

    /**
     * {@inheritdoc}
     */
    public function remove($name) : bool
    {
        $count = $this->client->sRem('queues', $name);
        $this->client->del($this->getKey($name));
        $this->client->del($this->getKey($name, self::TYPE_PROCESSING));

        return $count > 0;
    }

    /**
     * {@inheritdoc}
     */
    public function clear($name) : bool
    {
        $this->client->del($this->getKey($name));
        $this->client->del($this->getKey($name, self::TYPE_PROCESSING));

        return true;
    }

    /**
     * @param array ...$args
     *
     * @throws \BadMethodCallException
     *
     * @return mixed
     */
    private function eval(...$args)
    {
        $return = $this->client->eval(...$args);

        if ($this->client->getLastError()) {
            throw new \BadMethodCallException($this->client->getLastError());
        }

        return $return;
    }

    /**
     * {@inheritdoc}
     */
    public function count(string $name) : int
    {
        return $this->eval(
            "return redis.call('llen', KEYS[1]) + redis.call('zcard', KEYS[2])",
            [
                $this->getKey($name),
                $this->getKey($name, self::TYPE_PROCESSING)
            ],
            2
        );
    }

    /**
     * {@inheritdoc}
     */
    public function publish(string $name, string $payload) : bool
    {
        return $this->client->rpush($this->getKey($name), $payload) > 0;
    }

    /**
     * {@inheritdoc}
     */
    public function consume(string $name, callable $callback, callable $quit = null) : int
    {
        $quit = false;

        $quitFunction = function($quitNeeded) use (&$quit)
        {
            $quit = $quitNeeded;
        };

        $count = 0;
        while(!$quit)
        {
            $pop = $this->client->blPop($this->getKey($name), 1);

            if (sizeof($pop) > 0) {
                $count++;

                $message = $pop[1];

                $add = $this->client->zAdd($this->getKey($name, self::TYPE_PROCESSING), time(), $message);
                if ($add !== 1) {
                    throw new \LogicException(sprintf(
                        "Incorrect ZADD return with '%s' on queue '%s'",
                        $add,
                        $name
                    ));
                }

                try {
                    $return = $callback($quitFunction, $message);
                    $this->handleAck($name, $return, $message);
                } catch (FailureException $exception) {
                    $this->client->multi()
                        ->zRem($this->getKey($name, self::TYPE_PROCESSING), $message)
                        ->zAdd($this->getKey($name, self::TYPE_FAILED), time(), $message)
                    ->exec();

                    throw $exception;
                }
            }

            $callback($quitFunction);
        }

        return $count;
    }

    /**
     * {@inheritdoc}
     */
    protected function ack(string $name, $msg) : bool
    {
        $this->client->zRem($this->getKey($name, self::TYPE_PROCESSING), $msg);

        return true;
    }

    /**
     * {@inheritdoc}
     */

    protected function nack(string $name, $msg) : bool
    {
        return $this->publish($name, $msg);
    }

    const TYPE_QUEUE = 'queue';
    const TYPE_PROCESSING = 'processing';
    const TYPE_FAILED = 'failed';

    /**
     * @param string $name
     * @param string $type
     *
     * @return string
     */
    private function getKey(string $name, string $type = self::TYPE_QUEUE) : string
    {
        return $name . ':' . $type;
    }
}
