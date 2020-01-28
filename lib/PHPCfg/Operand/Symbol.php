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

class Symbol extends Operand
{
    public $original;
    public array $conditions = [];
    public bool $is_set = true;
    public ?Type $type;

    /**
     * Constructs a symbolic variable
     *
     * @param Operand|null $original The previous variable this was constructed from
     */
    public function __construct(Operand $original = null)
    {
        $this->original = $original;
    }

    public function addCondition($condition) {
        $this->conditions[] = $condition;
    }
}
