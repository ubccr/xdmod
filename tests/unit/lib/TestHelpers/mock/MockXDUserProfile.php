<?php

namespace UnitTests\TestHelpers\mock;

class MockXDUserProfile
{
    private $_data = [];

    public function fetchValue($ident)
    {
        if( isset($this->_data[$ident]) ) {
            return $this->_data[$ident];
        } else {
            return null;
        }
    }

    public function dropValue($ident): void
    {
        unset($this->_data[$ident]);
    }

    public function setValue($ident, $value): void
    {
        $this->_data[$ident] = $value;
    }

    public function save(): void
    {
    }
}
