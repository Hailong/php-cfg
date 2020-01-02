<?php

declare(strict_types=1);

/**
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg;

class CatchBlock extends Block
{
    /** @var array Node\Name Types of exceptions that are caught by this catch */
    public $catch = [];

    /** @var array|null Node\Name=>CatchBlock Type of exception to catch and the corresponding catch block */
    public $catches = [];

    /**
     * Constructor
     *
     * @param int blockId
     * @param array Node\Name $catch: Types of exceptions that are caught by this catch
     * @param array Node\Name=>CatchBlock $catches: Type of exception to catch and the corresponding catch block
     */
    function __construct(int $blockId, Block $block, array $catch, array $catches = null) {
        $this->blockId = $blockId;
        $this->children = $block->children;
        $this->catch[] = $catch;
        $this->catches[] = $catches;
    }
}
