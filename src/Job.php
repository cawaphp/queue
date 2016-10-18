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

class Job extends Envelope
{
    /**
     * @param string $class
     * @param array $args
     */
    public function __construct(string $class = null, array $args = [])
    {
        $this->headers['class'] = $class;
        parent::__construct($args);
    }

    /**
     * @return string
     */
    public function getClass() : string
    {
        return $this->headers['class'];
    }

    /**
     * @param string $class
     *
     * @return self|$this
     */
    public function setClass(string $class) : self
    {
        $this->headers['class'] = $class;

        return $this;
    }
}
