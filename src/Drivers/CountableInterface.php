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

interface CountableInterface
{
    /**
     * Count the number of messages in queue.
     * This can be a approximately number.
     *
     * @param string $name
     *
     * @return int
     */
    public function count(string $name) : int;
}
