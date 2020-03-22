<?php

declare(strict_types=1);

/**
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\Operand;

use PHPCfg\Operand;
use PHPTypes\Type;

class Reference extends Operand
{
    public $value;
    public $reference;

    public function __construct($value, $reference=null, ?Type $type=null)
    {
        $this->value = $value;
        $this->reference = $reference;
        if (null !== $type) {
            $this->type = $type;
        }
    }

    public function &getResult() {
        return $this;
    }
}
