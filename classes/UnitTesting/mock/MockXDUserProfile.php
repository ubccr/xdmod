<?php

namespace UnitTesting\mock;

class MockXDUserProfile
{
    private $_data = array();

    public function fetchValue($ident)
    {
        if( isset($this->_data[$ident]) ) {
            return $this->_data[$ident];
        } else {
            return null;
        }
    }

    public function dropValue($ident)
    {
        unset($this->_data[$ident]);
    }

    public function setValue($ident, $value)
    {
        $this->_data[$ident] = $value;
    }

    public function save()
    {
    }
}

?>
