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

namespace Cawa\Queue;

use Cawa\Date\DateTime;

class Envelope implements \JsonSerializable
{
    /**
     * @param mixed $body
     */
    public function __construct($body = null)
    {
        $this->body = $body;
    }

    //region Mutator

    /**
     * @var mixed
     */
    private $body;

    /**
     * @return mixed
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @param mixed $body
     *
     * @return Envelope
     */
    public function setBody($body)
    {
        $this->body = $body;

        return $this;
    }

    /**
     * @var array
     */
    protected $headers;

    /**
     * @return array
     */
    public function getHeaders() : array
    {
        return $this->headers;
    }

    /**
     * @param array $headers
     *
     * @return $this|Envelope
     */
    public function setHeaders(array $headers) : self
    {
        $this->headers = $headers;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getAdded() : DateTime
    {
        return DateTime::createFromTimestampUTC($this->headers['added']);
    }

    /**
     * @return array|EnvelopeHistory[]
     */
    public function getHistory() : array
    {
        return $this->headers['history'] ?: [];
    }

    //endregion

    //region Serialize

    /**
     * @param callable $callback
     *
     * @return callable
     */
    public static function callback(callable $callback) : callable
    {
        return function (Message $message) use ($callback) {
            if ($message->getMessage()) {
                $return = Envelope::unserialize($message->getMessage());
                $return->headers['history'][] = new EnvelopeHistory($message->getWorkerId());

                return $callback($message, $return);
            } else {
                return $callback($message);
            }
        };
    }

    /**
     * @return string
     */
    public function serialize() : string
    {
        $this->headers['added'] = time();

        return json_encode($this);
    }

    /**
     * @param string $data
     *
     * @return Envelope|static
     */
    public static function unserialize(string $data) : self
    {
        $data = json_decode($data, true);

        /** @var Envelope $return */
        $return = new $data['class']();
        unset($data['class']);

        $return->jsonUnserialize($data);

        return $return;
    }

    /**
     * @param array $data
     */
    public function jsonUnserialize(array $data)
    {
        $this->body = unserialize($data['body']);
        $this->headers = $data['headers'];
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return [
            'body' => serialize($this->body),
            'class' => get_class($this),
            'headers' => $this->headers,
        ];
    }

    //endregion
}
