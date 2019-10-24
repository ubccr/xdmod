<?php

namespace TestHelpers\mock;

class MockXDUser
{
    private $_profile = null;

    public function __construct()
    {
        $this->_profile = new MockXDUserProfile();
    }

    public function getProfile() {
        return $this->_profile;
    }
}
