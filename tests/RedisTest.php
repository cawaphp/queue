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

/**
 * Сáша frameworks tests.
 *
 * @author tchiotludo <http://github.com/tchiotludo>
 */

namespace CawaTest\Queue;

use Cawa\Core\DI;
use Cawa\Queue\QueueFactory;

class RedisTest extends AbstractTest
{
    use QueueFactory;

    /**
     *
     */
    public function setUp()
    {
        DI::config()->add([
            'queue' => [
                'default' => [
                    'type' => 'Redis',
                    'name' => 'testsqueue',
                    'config' => [
                        'host' => 'localhost',
                    ],
                ],
            ],
        ]);

        parent::setUp();
    }
}
