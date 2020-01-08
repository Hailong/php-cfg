<?php

declare(strict_types=1);

/**
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\Printer;

use Exception;
use PHPCfg\CatchableBlock;
use PHPCfg\CatchBlock;
use PHPCfg\Func;
use PHPCfg\Printer;
use PHPCfg\Script;
use phpDocumentor\GraphViz\Edge;
use phpDocumentor\GraphViz\Graph;
use phpDocumentor\GraphViz\Node;
use PhpParser\Node\Stmt\Foreach_;

class GraphViz extends Printer
{
    protected $options = [
        'graph' => [],
        'node' => [
            'shape' => 'rect',
        ],
        'edge' => [],
    ];

    protected $graph;

    public function __construct(array $options = [])
    {
        parent::__construct();
        $this->options = $options + $this->options;
    }

    public function printScript(Script $script)
    {
        $i = 0;
        $graph = $this->createGraph();
        // Print main function block
        $this->printFuncWithHeader($script->main, $graph, 'func_'.++$i.'_');
        // Print function blocks
        foreach ($script->functions as $func) {
            $this->printFuncWithHeader($func, $graph, 'func_'.++$i.'_');
        }
        // Print catch blocks
        if (isset($script->catches)) {
            foreach ($script->catches as $catch_block) {
                $this->printCatchWithHeader($catch_block, $graph, 'catch_'.$catch_block->blockId.'_');
            }
        }

        return $graph;
    }

    public function printFunc(Func $func)
    {
        $graph = $this->createGraph();
        $this->printFuncInfo($func, $graph, '');

        return $graph;
    }

    public function printVars(Func $func)
    {
        $graph = Graph::create('vars');
        foreach ($this->options['graph'] as $name => $value) {
            $setter = 'set'.$name;
            $graph->{$setter}($value);
        }
        $rendered = $this->renderBlock($func->cfg);
        $nodes = new \SplObjectStorage();
        foreach ($rendered['varIds'] as $var) {
            if (empty($var->ops) && empty($var->usages)) {
                continue;
            }
            $id = $rendered['varIds'][$var];
            $output = $this->renderOperand($var);
            $nodes[$var] = $this->createNode('var_'.$id, $output);
            $graph->setNode($nodes[$var]);
        }
        foreach ($rendered['varIds'] as $var) {
            foreach ($var->ops as $write) {
                $b = $write->getAttribute('block');
                foreach ($write->getVariableNames() as $varName) {
                    $vs = $write->{$varName};
                    if (! is_array($vs)) {
                        $vs = [$vs];
                    }
                    foreach ($vs as $v) {
                        if (! $v || $write->isWriteVariable($varName) || ! $nodes->contains($v)) {
                            continue;
                        }
                        $edge = $this->createEdge($nodes[$v], $nodes[$var]);
                        if ($b) {
                            $edge->setlabel('Block<'.$rendered['blockIds'][$b].'>'.$write->getType().':'.$varName);
                        } else {
                            $edge->setlabel($write->getType().':'.$varName);
                        }
                        $graph->link($edge);
                    }
                }
            }
        }

        return $graph;
    }

    protected function printFuncWithHeader(Func $func, Graph $graph, $prefix)
    {
        $name = $func->getScopedName();
        $function_header = "Function ${name}():" . ((null !== $func->getAttribute('path')) ? '\nPath: '.$func->getAttribute('path') : '');
        $header = $this->createNode(
            $prefix.'header', $function_header
        );
        $graph->setNode($header);

        $start = $this->printFuncInfo($func, $graph, $prefix);
        $edge = $this->createEdge($header, $start);
        $graph->link($edge);
    }

    protected function printCatchWithHeader(CatchBlock $catch, Graph $graph, $prefix)
    {
        $types = '';
        foreach ($catch->catch as $catch_types) {
            foreach ($catch_types as $catch_type) {
                $types = $catch_type . ' | ';
            }
        }
        $types = substr($types, 0, -3);
        $header = $this->createNode(
            $prefix.'header', sprintf("Catch_%s(%s):", $catch->blockId, $types)
        );
        $graph->setNode($header);

        $start = $this->printCatchInfo($catch, $graph, $prefix);
        $edge = $this->createEdge($header, $start);
        $graph->link($edge);
    }

    protected function printFuncInfo(Func $func, Graph $graph, $prefix)
    {
        $rendered = $this->render($func);
        $nodes = new \SplObjectStorage();
        foreach ($rendered['blocks'] as $block) {
            $blockId = $block->blockId;
            $ops = $rendered['blocks'][$block];
            $output = '';
            foreach ($ops as $op) {
                $output .= $this->indent("\n".$op['label']);
            }
            $block_content = $prefix.'block_'.$block->blockId;
            if ($block instanceof CatchableBlock) {
                $exception_header = "\nCatches:";
                $exception_content = "";
                assert(sizeof($block->catches) > 0);
                foreach ($block->catches as $catch_blocks) {
                    foreach ($catch_blocks as $catch_block) {
                        foreach ($catch_block->catch as $catch) {
                            foreach($catch as $exception_type) {
                                $exception_content .= $exception_type . ' | ';
                            }
                            $exception_content = substr($exception_content, 0, -3);
                            $exception_content .= ' -> ' . 'Catch_' . $catch_block->blockId . "\n";
                        }
                    }
                }
                $block_content .= $exception_header . $this->indent("\n" . $exception_content);
            }
            $block_content .= $output;
            $nodes[$block] = $this->createNode($prefix.'block_'.$blockId, $block_content, $block->covered ? 'green' : 'red');
            $graph->setNode($nodes[$block]);
        }

        foreach ($rendered['blocks'] as $block) {
            foreach ($rendered['blocks'][$block] as $op) {
                foreach ($op['childBlocks'] as $child) {
                    $edge = $this->createEdge($nodes[$block], $nodes[$child['block']]);
                    $edge->setlabel($child['name']);
                    $graph->link($edge);
                }
            }
        }

        return $nodes[$func->cfg];
    }

    protected function printCatchInfo(CatchBlock $block, Graph $graph, $prefix)
    {
        $rendered = $this->renderBlock($block);
        $nodes = new \SplObjectStorage();
        foreach ($rendered['blocks'] as $block) {
            $blockId = $block->blockId+1;
            $ops = $rendered['blocks'][$block];
            $output = '';
            foreach ($ops as $op) {
                $output .= $this->indent("\n".$op['label']);
            }
            $block_content = $prefix.'block_'.$blockId;
            $block_content .= $output;
            $nodes[$block] = $this->createNode($prefix.'block_'.$blockId, $block_content, 'gray');
            $graph->setNode($nodes[$block]);
        }

        foreach ($rendered['blocks'] as $block) {
            foreach ($rendered['blocks'][$block] as $op) {
                foreach ($op['childBlocks'] as $child) {
                    $edge = $this->createEdge($nodes[$block], $nodes[$child['block']]);
                    $edge->setlabel($child['name']);
                    $graph->link($edge);
                }
            }
        }

        return $nodes[$block];
    }

    /**
     * @param string $str
     */
    protected function indent($str, $levels = 1): string
    {
        if ($levels > 1) {
            $str = $this->indent($str, $levels - 1);
        }

        return str_replace(["\n", '\\l'], '\\l    ', $str);
    }

    private function createGraph()
    {
        $graph = Graph::create('cfg');
        foreach ($this->options['graph'] as $name => $value) {
            $setter = 'set'.$name;
            $graph->{$setter}($value);
        }

        return $graph;
    }

    private function createNode($id, $content, $color=null)
    {
        $node = new Node($id, $content);
        if (isset($color)) {
            $node->setstyle('filled');
            $node->setfillcolor($color);
        }
        foreach ($this->options['node'] as $name => $value) {
            $node->{'set'.$name}($value);
        }

        return $node;
    }

    private function createEdge(Node $from, Node $to)
    {
        $edge = new Edge($from, $to);
        foreach ($this->options['edge'] as $name => $value) {
            $edge->{'set'.$name}($value);
        }

        return $edge;
    }
}
