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

use Cawa\Events\DispatcherFactory;
use Cawa\Events\TimerEvent;
use Cawa\Queue\Drivers\AbstractDriver;
use Cawa\Queue\Drivers\CountableInterface;

class Queue
{
    use DispatcherFactory;

    /**
     * @var string
     */
    private $type;

    /**
     * @return string
     */
    public function getType() : string
    {
        return $this->type;
    }

    /**
     * @var string
     */
    private $name;

    /**
     * @return string
     */
    public function getName() : string
    {
        return $this->name;
    }

    /**
     * @var AbstractDriver
     */
    private $storage;

    /**
     * @return AbstractDriver
     */
    public function getStorage() : AbstractDriver
    {
        return $this->storage;
    }

    /**
     * @param array $config
     */
    public function __construct(array $config)
    {
        if (!isset($config['name'])) {
            throw new \InvalidArgumentException('Missing name');
        }

        if (!isset($config['type'])) {
            throw new \InvalidArgumentException('Missing type');
        }

        $this->type = $config['type'];
        $this->name = $config['name'];

        $event = $this->getTimerEvent(':create');

        $storageClass = explode('\\', get_class());
        array_pop($storageClass);
        $storageClass[] = 'Drivers';
        $storageClass[] = $config['type'];
        $storageClass = implode('\\', $storageClass);

        /** @var AbstractDriver $storage */
        $storage = new $storageClass($config['config'] ?? null);
        $this->storage = $storage;

        $this->storage->create($this->name);

        self::emit($event);

        return $this;
    }

    /**
     * @param string $method
     *
     * @return TimerEvent
     */
    protected function getTimerEvent(string $method) : TimerEvent
    {
        $method = substr(strrchr($method, ':'), 1);

        $event = new TimerEvent('queue.' . strtolower($this->type) . ucfirst($method), [
            'name' => $this->name,
        ]);

        return $event;
    }

    /**
     * @return bool
     */
    public function create() : bool
    {
        $event = $this->getTimerEvent(__METHOD__);

        $return = $this->storage->create($this->name);

        self::emit($event);

        return $return;
    }

    /**
     * @return bool
     */
    public function clear() : bool
    {
        $event = $this->getTimerEvent(__METHOD__);

        $return = $this->storage->clear($this->name);

        self::emit($event);

        return $return;
    }

    /**
     * @return bool
     */
    public function remove() : bool
    {
        $event = $this->getTimerEvent(__METHOD__);

        $return = $this->storage->remove($this->name);

        self::emit($event);

        return $return;
    }

    /**
     * @return int
     */
    public function count() : int
    {
        if (!$this->storage instanceof CountableInterface) {
            throw new \RuntimeException(get_class($this->storage) . ' is not coutable');
        }

        $event = $this->getTimerEvent(__METHOD__);

        $return = $this->storage->count($this->name);

        $event->addData([
            'count' => $return,
        ]);

        self::emit($event);

        return $return;
    }

    /**
     * @param string $message
     *
     * @return bool
     */
    public function publish(string $message) : bool
    {
        $event = $this->getTimerEvent(__METHOD__);

        $return = $this->storage->publish($this->name, $message);

        $event->addData([
            'body' => $message,
        ]);

        self::emit($event);

        return $return;
    }

    /**
     * @param callable $callback
     *
     * @return int
     */
    public function consume(callable $callback) : int
    {
        $event = $this->getTimerEvent(__METHOD__);

        $count = $this->storage->consume($this->name, $callback);

        $event->addData([
            'count' => $count,
        ]);

        self::emit($event);

        return $count;
    }


    /**
     * @param Envelope $envelope
     *
     * @return bool
     */
    public function queue(Envelope $envelope) : bool
    {
        $event = $this->getTimerEvent(__METHOD__);

        $return = $this->storage->publish($this->name, $envelope->serialize());

        $event->addData([
            'body' => $envelope->getBody(),
            'headers' => $envelope->getHeaders(),
        ]);

        self::emit($event);

        return $return;
    }

    /**
     * @param callable $callback
     *
     * @return int
     */
    public function enqueue(callable $callback) : int
    {
        $event = $this->getTimerEvent(__METHOD__);

        $count = $this->storage->consume($this->name, function($quit, string $message = null) use ($callback)
        {
            if ($message) {
                return $callback($quit, Envelope::unserialize($message));
            } else {
                return $callback($quit);
            }
        });

        $event->addData([
            'count' => $count,
        ]);

        self::emit($event);

        return $count;
    }
}
