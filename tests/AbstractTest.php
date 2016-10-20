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

/**
 * Сáша frameworks tests
 *
 * @author tchiotludo <http://github.com/tchiotludo>
 */
namespace CawaTest\Queue;

use Cawa\Date\DateTime;
use Cawa\Queue\Drivers\CountableInterface;
use Cawa\Queue\Envelope;
use Cawa\Queue\Queue;
use Cawa\Queue\QueueFactory;
use PHPUnit_Framework_TestCase as TestCase;

abstract class AbstractTest extends TestCase
{
    use QueueFactory;

    /**
     * @var Queue
     */
    protected $queue;

    /**
     *
     */
    public function setUp()
    {
        $this->queue = self::queue();
        $this->queue->getStorage()->connect();
        $this->queue->remove();
        $this->queue->create();
    }

    /**
     * @param array $publishs
     *
     * @dataProvider simpleDataProvider
     */
    public function testPublish(array $publishs)
    {
        foreach ($publishs as $publish) {
            $this->queue->publish((string) $publish);
        }

        if ($this->queue->getStorage() instanceof CountableInterface) {
            $this->assertEquals(sizeof($publishs), $this->queue->count());
        } else {
            $this->assertEquals(sizeof($publishs), sizeof($publishs));
        }

        $this->queue->clear();
    }

    /**
     * @param array $publishs
     *
     * @dataProvider simpleDataProvider
     */
    public function testConsume(array $publishs)
    {
        foreach ($publishs as $publish) {
            $this->queue->publish((string) $publish);
        }

        $time = time();
        $received = [];
        $count = $this->queue->consume(function (callable $quit, $message = null) use (
            &$received,
            $publishs,
            $time
        ) {
            if ($message) {
                $received[] = (string) $message;

                return true;
            }

            $this->shouldQuit($quit, sizeof($received) == sizeof($publishs), $time);

            return null;
        });

        $this->assertEquals($publishs, $received);
        $this->assertEquals(sizeof($publishs), $count);
    }

    /**
     * @param callable $quit
     * @param bool $mustQuit
     * @param int $time
     */
    public function shouldQuit(callable $quit, bool $mustQuit, int $time)
    {
        if ($mustQuit) {
            $quit(true);
        } else if ($this->queue->getStorage() instanceof CountableInterface && $this->queue->count() == 0) {
            $quit(true);
        } else if ($time + 3 < time()) {
            $quit(true);
        }
    }

    /**
     * @param array $publishs
     *
     * @dataProvider dataProvider
     */
    public function testEnvelope(array $publishs)
    {
        foreach ($publishs as $publish) {
            $this->queue->queue((new Envelope($publish)));
        }

        $time = time();
        $received = [];
        $this->queue->enqueue(function (callable $quit, Envelope $envelope = null) use (&$received, $publishs, $time) {
            if ($envelope) {
                $this->assertInstanceOf(DateTime::class, $envelope->getAdded());
                $received[] = $envelope->getBody();

                return true;
            }

            $this->shouldQuit($quit, sizeof($received) == sizeof($publishs), $time);

            return null;
        });

        $this->assertEquals($publishs, $received);
    }

    /**
     *
     */
    public function testExceptionNonAck()
    {
        $this->queue->publish('string');

        $this->expectException(\RuntimeException::class);
        $time = time();
        $mustQuit = false;
        $this->queue->consume(function (callable $quit, $message = null) use (&$received, $time, &$mustQuit) {
            if ($message) {
                $mustQuit = true;
            }

            $this->shouldQuit($quit, $mustQuit, $time);

            return null;
        });
    }

    /**
     * @return array
     */
    public function dataProvider()
    {
        return array_merge($this->simpleDataProvider(), [
            [[null]],
            [[[1, 2]]],
            [['string1', 'string2']],
            [[0, 1, 2, 3, 4, 5, 6, 7, 8, 9]],
        ]);
    }

    /**
     * @return array
     */
    public function simpleDataProvider()
    {
        return [
            [['string']],
            [[1]],
            [[true]],
            [[1.123456789]],
        ];
    }
}
