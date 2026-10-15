<?php
/**
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace Xdmod;

use Iterator;
use OutOfBoundsException;

/**
 * A set of nodes.
 */
class NodeSet implements Iterator
{

    /**
     * Parser for compressed host list strings.
     *
     * @var HostListParser
     */
    private static $parser;

    /**
     * Current position when iterating through nodes.
     *
     * @var int
     */
    private $position = 0;

    /**
     * The nodes in the node set.
     *
     * @var array
     */
    private $nodes = array();

    /**
     * Create a node set from a compressed host list string.
     *
     * @param string $hostList Host list in compressed format.
     *
     * @return NodeSet
     */
    public static function createFromCompressedHostList($hostList)
    {
        if (!isset(static::$parser)) {
            static::$parser = new HostListParser();
        }

        $hosts = static::$parser->expandHostList($hostList);

        return new static($hosts);
    }

    /**
     * Constructor.
     */
    public function __construct(array $nodes = array())
    {
        $this->nodes = array_unique($nodes);

        // Reset array keys.
        $this->nodes = array_values($this->nodes);
    }

    /**
     * Add a node to the node list.
     *
     * @param string $node The name of the node.
     */
    public function addNode($node)
    {
        if (!in_array($node, $this->nodes)) {
            $this->nodes[] = $node;
        }
    }

    /**
     * Remove a node from the node list.
     *
     * @param string $node The name of the node.
     */
    public function removeNode($node)
    {
        $this->nodes = array_filter(
            $this->nodes,
            function ($v) use ($node) {
                return $v !== $node;
            }
        );

        // Reset array keys.
        $this->nodes = array_values($this->nodes);
    }

    /**
     * @see Iterator
     */
    public function current(): mixed
    {
        if (!$this->valid()) {
            throw new OutOfBoundsException();
        }

        return $this->nodes[$this->position];
    }

    /**
     * @see Iterator
     */
    public function key(): mixed
    {
        return $this->position;
    }

    /**
     * @see Iterator
     */
    public function next(): void
    {
        ++$this->position;
    }

    /**
     * @see Iterator
     */
    public function rewind(): void
    {
        $this->position = 0;
    }

    /**
     * @see Iterator
     */
    public function valid(): bool
    {
        return isset($this->nodes[$this->position]);
    }
}
