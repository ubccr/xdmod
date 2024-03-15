<?php
/**
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace UnitTests\OpenXdmod\Tests;

use Xdmod\NodeSet;

/**
 * NodeSet test class
 */
class NodeSetTest extends \PHPUnit\Framework\TestCase
{
    public function testNodeSetIterator()
    {
        $nodes = array('d14n09', 'd14n11');

        $nodeSet = new NodeSet($nodes);

        $nodeSetNodes = array();

        foreach ($nodeSet as $node) {
            $nodeSetNodes[] = $node;
            $this->assertContains($node, $nodes);
        }

        $this->assertEquals($nodes, $nodeSetNodes);
    }

    public function testNodeSetFromCompressedHostList()
    {
        $nodes = array('d14n09', 'd14n11');
        $hostList = 'd14n[09,11]';

        $nodeSet = NodeSet::createFromCompressedHostList($hostList);

        $nodeSetNodes = array();

        foreach ($nodeSet as $node) {
            $nodeSetNodes[] = $node;
            $this->assertContains($node, $nodes);
        }

        $this->assertEquals($nodes, $nodeSetNodes);
    }

    public function testAddNode()
    {
        $nodes = array('d14n09', 'd14n11');
        $addNode = 'd14n13';

        $nodeSet = new NodeSet($nodes);

        $nodeSet->addNode($addNode);
        $nodes[] = $addNode;

        $nodeSetNodes = array();

        foreach ($nodeSet as $node) {
            $nodeSetNodes[] = $node;
        }

        $this->assertEquals($nodes, $nodeSetNodes);
    }

    public function testRemoveNode()
    {
        $nodes = array('d14n09', 'd14n11');
        $removeNode = 'd14n09';

        $nodeSet = new NodeSet($nodes);

        $nodeSet->removeNode($removeNode);

        $nodes = array('d14n11');

        $nodeSetNodes = array();

        foreach ($nodeSet as $node) {
            $nodeSetNodes[] = $node;
        }

        $this->assertEquals($nodes, $nodeSetNodes);
    }
}
