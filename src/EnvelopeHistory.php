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

use Cawa\Date\DateTime;

class EnvelopeHistory implements \JsonSerializable
{
    /**
     * @var int
     *
     */
    private $date;

    /**
     * @var string
     */
    private $workerId;

    /**
     * @return DateTime
     */
    public function getDate() : DateTime
    {
        return DateTime::createFromTimestampUTC($this->date);
    }

    /**
     * @param string $workerId
     */
    public function __construct(string $workerId)
    {
        $this->workerId = $workerId;
        $this->date = time();
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}
