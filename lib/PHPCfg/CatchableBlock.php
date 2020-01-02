<?php

declare(strict_types=1);

/**
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg;

class CatchableBlock extends Block
{
    /** @var array CatchBlock Catch blocks */
    public $catches = [];

    /**
     * Constructor
     *
     * @param self $parent
     * @param integer $blockId
     * @param array array CatchBlock Catch blocks
     */
    function __construct(Block $parent = null, $blockId = -1, array $catches = null)
    {
        parent::__construct($parent, $blockId);
        $this->catches[] = $catches;
    }
}
