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

namespace Cawa\Queue\Exceptions;

use Throwable;

class ManualRequeueException extends \Exception
{
    /**
     * @var bool
     */
    private $exit = false;

    /**
     * @return bool
     */
    public function isExit() : bool
    {
        return $this->exit;
    }

    /**
     * @param bool $exit
     * @param string $message
     * @param Throwable|null $previous
     */
    public function __construct(bool $exit = false, $message = "", Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
