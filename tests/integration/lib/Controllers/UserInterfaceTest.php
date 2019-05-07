<?php

namespace IntegrationTests\Controllers;

use CCR\Json;

class UserInterfaceTest extends BaseUserAdminTest
{

    /**
     * Test the `html/controllers/user_interface/get_menus.php` endpoint.
     *
     * @dataProvider provideTestGetMenus
     *
     * @param array $options
     */
    public function testGetMenus(array $options)
    {

    }

    public function provideTestGetMenus()
    {
        return JSON::loadFile(
            $this->getTestFiles()->getFile('user_interface', 'get_menus', 'input')
        );
    }

}
