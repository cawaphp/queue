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

use Cawa\HttpClient\HttpClient;
use Cawa\Net\Uri;
use Cawa\Queue\Exceptions\FailureException;
use Cawa\Queue\Message;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class Amqp extends AbstractDriver
{
    /**
     * @var AMQPStreamConnection
     */
    protected $client;

    /**
     * @var AMQPChannel
     */
    protected $channel;

    /**
     * @var HttpClient
     */
    protected $httpClient;

    /**
     * {@inheritdoc}
     */
    public function connect() : bool
    {
        if (!$this->options['url'] instanceof Uri) {
            $uri = new Uri($this->options['url']);
        } else {
            $uri = $this->options['url'];
        }

        $this->client = new AMQPStreamConnection(
            $uri->getHost(),
            $uri->getPort() ?: 5672,
            $uri->getUser(),
            $uri->getPassword(),
            $uri->getPath()
        );
        $this->channel = $this->client->channel();

        if (isset($this->options['management'])) {
            $this->httpClient = new HttpClient();
            $this->httpClient->setBaseUri((clone $uri)
                ->setScheme('http')
                ->setPort($this->options['management']['port'] ?? 15672)
                ->setPath('/api/queues/' . urlencode($uri->getPath()))
            );
        }

        if ($this->options['wait'] ?? false) {
            $this->channel->confirm_select();
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function disconnect() : bool
    {
        $this->channel->close();
        $this->client->close();

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function list() : array
    {
        $return = [];

        $response = json_decode($this->httpClient->get('/')->getBody(), true);
        foreach ($response as $queue) {
            $return[] = $queue['name'];
        }

        return $return;
    }

    /**
     * {@inheritdoc}
     */
    public function create($name) : bool
    {
        $return = $this->channel->queue_declare(
            $name,
            $this->options['queue']['passive'] ?? false,
            $this->options['queue']['durable'] ?? true,
            $this->options['queue']['exclusive'] ?? false,
            $this->options['queue']['auto_delete'] ?? false
        );

        list($created) = $return;

        return $created == $name;
    }


    /**
     * {@inheritdoc}
     */
    public function remove($name) : bool
    {
        $count = $this->channel->queue_delete($name);

        return $count > 0;
    }


    /**
     * {@inheritdoc}
     */
    public function clear($name) : bool
    {
        $count = $this->channel->queue_purge($name);

        return $count > 0;
    }

    /**
     * {@inheritdoc}
     */
    public function publish(string $name, string $payload) : bool
    {
        $envelope = new AMQPMessage($payload);
        return $this->publishMessage($name, $envelope);
    }

    /**
     * @param string $name
     * @param AMQPMessage $message
     *
     * @return bool
     */
    private function publishMessage(string $name, AMQPMessage $message) : bool
    {
        $this->channel->basic_publish(
            $message,
            '',
            $name,
            $this->options['wait'] ?? false
        );

        if ($this->options['wait'] ?? false) {
            $this->channel->wait_for_pending_acks_returns();
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function consume(string $name, callable $callback, callable $quit = null) : int
    {
        $count = 0;
        $quit = false;

        $quitFunction = function($quitNeeded) use (&$quit)
        {
            $quit = $quitNeeded;
        };

        $this->workerId = $this->channel->basic_consume(
            $name,
            '',
            false,
            false,
            false,
            false,
            function(AMQPMessage $message) use ($callback, &$count, &$quitFunction, $name)
            {
                $count++;
                try {
                    $return = $callback((new Message($quitFunction, $this->workerId))
                        ->setMessage($message->getBody())
                    );


                    if (!is_null($return) && $return === true) {
                        $this->channel->basic_ack($message->delivery_info['delivery_tag']);
                    } else if (!is_null($return) && $return === false) {
                        $this->channel->basic_nack($message->delivery_info['delivery_tag']);
                    } else {
                        throw new \RuntimeException('Envelope must be (n)acked');
                    }
                } catch (FailureException $exception) {
                    $this->publishMessage($name . '_failed', new AMQPMessage($exception->getQueueMessage()->getMessage()));
                }
            });

        while (count($this->channel->callbacks)) {
            $read = [$this->client->getSocket()];
            $write = null;
            $except = null;
            if (false === ($num_changed_streams = stream_select($read, $write, $except, 1))) {
                /* Error handling */
            } elseif ($num_changed_streams > 0) {
                $this->channel->wait();
            }

            $callback(new Message($quitFunction, $this->workerId));

            if ($quit == true) {
                $this->channel->basic_cancel($this->workerId);
            }
        }

        return $count;
    }
}
