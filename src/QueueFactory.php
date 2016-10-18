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

use Cawa\Core\DI;

trait QueueFactory
{
    /**
     * @param string $name
     *
     * @return Queue
     */
    private static function queue(string $name = null) : Queue
    {
        if ($return = DI::get(__METHOD__, $name)) {
            return $return;
        }

        $config = DI::config()->get('queue/' . ($name ?: 'default'));
        $db = new Queue($config);

        return DI::set(__METHOD__, $name, $db);
    }
}
