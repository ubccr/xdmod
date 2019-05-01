<?php
/**
 * Component tests for ETL VariableStore
 *
 * @author Steve Gallo <smgallo@buffalo.edu>
 * @date 2018-05-04
 *
 */

namespace UnitTesting\ETL\VariableStore;

use ETL\VariableStore;

class VariableStoreTest extends \PHPUnit_Framework_TestCase
{

    /**
     * Test constructor and initializers.
     */

    public function testConstructor()
    {
        // Empty store
        $store = new VariableStore();
        $this->assertCount(0, $store->toArray(), 'Store not empty');

        $init = array(
            'variable_1' => 'value_1',
            'variable_2' => 'value_3',
            'variable_3' => 'value_2'
        );
        $store1 = new VariableStore($init);
        $this->assertCount(3, $store1->toArray(), 'Initialize store with array');

        $store2 = new VariableStore($store1);
        $this->assertCount(3, $store2->toArray(), 'Initialize store with VariableStore');
    }

    /**
     * Test constructor with invalid initializer.
     *
     * @expectedException Exception
     */

    public function testConstructorBadInitializer()
    {
        new VariableStore('bad initializer');
    }

    /**
     * Test setting and retrieving of variables
     */

    public function testSettersAndGetters()
    {
        $store = new VariableStore();
        $store->variable1 = 'value1';
        $store->variable2 = 'value2';

        $this->assertEquals('value2', $store->variable2);
        $this->assertEquals(
            array('variable1' => 'value1', 'variable2' => 'value2'),
            $store->toArray()
        );

        $store->add(array('variable1' => 'new value', 'variable3' => 'value3'));

        $this->assertCount(3, $store->toArray(), 'Adding via add()');
        $this->assertNotEquals('new value', $store->variable1, 'Do not overwrite by default');
        $this->assertEquals('value3', $store->variable3, 'Adding values via add()');
        $this->assertNull($store->variable_not_set, 'Undefined variable');
        $this->assertTrue(isset($store->variable1), 'Testing isset()');
        $this->assertFalse(isset($store->variable_not_set), 'Testing isset()');

        // Clear a varaible
        $store->variable3 = null;
        $this->assertNull($store->variable3, 'Setting variable to NULL');

        // Case sensitivity
        $this->assertNull($store->VARIABLE1, 'Case sensitivity');
    }

    /**
     * Test non-scalar value
     *
     * @expectedException Exception
     */

    public function testNonScalarValue()
    {
        $store = new VariableStore();
        $store->first = array(1, 2, 3);
    }

    /**
     * Test overwriting of variables.
     */

    public function testOverwrite()
    {
        $store = new VariableStore();
        $store->variable1 = 'value1';
        $store->variable2 = 'value2';

        $store->variable1 = 'new value';
        $this->assertNotEquals('new value', $store->variable1, 'Do not overwrite by default');

        $store->overwrite('variable1', 'new value');
        $this->assertEquals('new value', $store->variable1, 'Testing overwrite()');

        $variables = array(
            'variable1' => 'overwritten',
            'variable3' => 'new variable'
        );
        $store->add($variables, true);
        $this->assertEquals('overwritten', $store->variable1, 'Overwrite via add(overwrite=true)');
        $this->assertEquals('new variable', $store->variable3, 'New variable via add(overwrite=true)');
    }

    /**
     * Test variable substitution.
     */

    public function testSubstitution()
    {
        $string = 'The ${color} ${animal} jumped over the ${OBJECT} ${num} times';
        $init = array(
            'color'  => 'brown',
            'animal' => 'fox',
            'OBJECT' => 'fence',
            'num'    => 4
        );
        $store = new VariableStore($init);

        $this->assertEquals(
            'The brown fox jumped over the fence 4 times',
            $store->substitute($string),
            'substitute()'
        );

        // Unset a variable and get details on what variables were substituted or not

        $details = array();
        $store->num = null;
        $substituted = $store->substitute($string, null, $details);
        $this->assertEquals(
            'The brown fox jumped over the fence ${num} times',
            $substituted,
            'substitute() with details'
        );
        $this->assertTrue(in_array('num', $details['unsubstituted']), 'substitute() unsubstituted variables');
        $this->assertCount(3, $details['substituted'], 'substitute() substituted variables');
    }
} // class VariableStoreTest
