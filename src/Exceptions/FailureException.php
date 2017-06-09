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

namespace Cawa\Queue\Exceptions;

use Cawa\Queue\Message;
use Exception;

class FailureException extends \Exception
{
    /**
     * @var Message
     */
    protected $queueMessage;

    /**
     * @return Message
     */
    public function getQueueMessage() : Message
    {
        return $this->queueMessage;
    }

    /**
     * @param Message $queueMessage
     * @param string $message
     * @param Exception $previous
     */
    public function __construct(Message $queueMessage, $message, Exception $previous = null)
    {
        $this->queueMessage = $queueMessage;
        parent::__construct($message, 0, $previous);
    }
}
