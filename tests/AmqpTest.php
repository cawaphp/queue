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

class AmqpTest extends AbstractTest
{
    use QueueFactory;

    /**
     *
     */
    public function setUp()
    {
        if (!defined('AMQP_DEBUG')) {
            define('AMQP_DEBUG', false);
        }

        DI::config()->add([
            'queue' => [
                'default' => [
                    'type' => 'Amqp',
                    'name' => 'testsqueue',
                    'config' => [
                        'url' => 'rabbitmq://guest:guest@localhost/',
                        'wait' => true,
                        'queue' => [
                            'durable' => true,
                        ],
                        'management' => [
                        ],
                    ],
                ],
            ],
        ]);

        parent::setUp();
    }

    /**
     *
     */
    public function tearDown()
    {
        $this->queue->getStorage()->disconnect();
    }
}
