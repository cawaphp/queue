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
     * @param string $name config key or class name
     *
     * @return Queue
     */
    private static function queue(string $name = null) : Queue
    {
        list($container, $config, $return) = DI::detect(__METHOD__, 'queue', $name);

        if ($return) {
            return $return;
        }

        $db = new Queue($config);

        return DI::set(__METHOD__, $container, $db);
    }
}
