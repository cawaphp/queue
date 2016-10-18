# Сáша Queue
-----

## Warning
Be aware that this package is still in heavy developpement.
Some breaking change will occure. Thank's for your comprehension.

## Features
* Drivers
   * Amqp
   * Redis

## Basic Usage

```php
use Cawa\Queue\Envelope;
use \Cawa\Queue\QueueFactory;

class Example
{
    use QueueFactory;

    public function consume()
    {
        $queue = self::queue();

        $queue->consume(function (callable $quit, Envelope $envelope = null) {
            if ($envelope) {
                trace($envelope);
            }
            return true;
        });
    }
    
    public function publish()
    {
        $queue = self::queue();
        $queue->publish(new Envelope('publish'));
    }
}
```

## About

### License

Cawa is licensed under the GPL v3 License - see the `LICENSE` file for details
