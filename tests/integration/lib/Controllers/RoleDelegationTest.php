<?php

namespace IntegrationTests\Controllers;

use CCR\Json;

/**
 * This class conducts a series of tests meant to exercise the promotion /
 * demotion functionality available to users w/ the 'Center Director' acl.
 * The basics of this functionality are as follows:
 * - A 'Center Director' should be able to:
 *   - promote users that are affiliated with their center to 'Center Staff' for
 *     their center.
 *   - demote users that are 'Center Staff' for their center via removing the
 *     'Center Staff' acl that is affiliated with their center.
 */
class RoleDelegationTest extends BaseUserAdminTest
{

    /**
     * @var array
     */
    private $config;

    protected function setup(): void
    {
        parent::setup();
        $this->config = json_decode(file_get_contents(__DIR__ . '/../../../ci/testing.json'), true);
    }

    /**
     * Run through all the tests that are expected to pass.
     *
     * @dataProvider provideSuccessfulRoleDelegation
     *
     * @param array $options
     * @throws \Exception if there is a problem authenticating as the provided user.
     */
    public function testSuccessfulRoleDelegation(array $options)
    {
        $user = $options['user'];
        $operation = $options['operation'];
        $target = $options['target'];
        $expectedFileName = $options['expected'];

        $data = array(
            'operation' => $operation
        );

        $memberId = $this->getMemberId($target);

        if(isset($memberId)) {
            $data['member_id'] = $memberId;
        }

        $this->helper->authenticate($user);

        $response = $this->helper->post('controllers/role_manager.php', null, $data);
        $this->validateResponse($response, 200, 'text/html; charset=UTF-8');

        $this->assertTrue(is_string($response[0]), "Response data not as expected. Received: " . json_encode($response[0]));
        $content = json_decode($response[0], true);
        $expected = JSON::loadFile($this->getTestFiles()->getFile('role_delegation', $expectedFileName));

        $this->assertEquals($expected, $content);

        $this->helper->logout();
    }

    /**
     * @return array|object
     * @throws \Exception
     */
    public function provideSuccessfulRoleDelegation()
    {
        return JSON::loadFile(
            $this->getTestFiles()->getFile('role_delegation', 'role_delegation_valid', 'input')
        );
    }

    /**
     * Run through the tests that are expected to fail due to providing invalid
     * parameter data
     *
     * @dataProvider provideInvalidRoleDelegation
     *
     * @param array $options
     * @throws \Exception
     */
    public function testInvalidRoleDelegation(array $options)
    {
        $user = $options['user'];
        $operation = $options['operation'];
        $target = $options['target'];
        $expectedFileName = $options['expected'];

        $data = array(
            'operation' => $operation
        );

        $memberId = $this->getMemberId($target);

        if(isset($memberId)) {
            $data['member_id'] = $memberId;
        }

        $this->helper->authenticate($user);

        $response = $this->helper->post('controllers/role_manager.php', null, $data);
        $this->validateResponse($response, 200, 'application/json');

        $content = $response[0];
        $expected = JSON::loadFile($this->getTestFiles()->getFile('role_delegation', $expectedFileName));

        $this->assertEquals($expected, $content);

        $this->helper->logout();
    }

    /**
     * @return array|object
     * @throws \Exception
     */
    public function provideInvalidRoleDelegation()
    {
        return JSON::loadFile(
            $this->getTestFiles()->getFile('role_delegation', 'role_delegation_invalid', 'input')
        );
    }

    /**
     * Run through tests that are expected to fail due to the user conducting them
     * not being authorized to perform them.
     *
     * @dataProvider provideUnauthorizedRoleDelegation
     * @param array $options
     * @throws \Exception
     */
    public function testUnauthorizedRoleDelegation(array $options)
    {
        $user = $options['user'];
        $operation = $options['operation'];
        $target = $options['target'];
        $expectedFileName = $options['expected'];

        $data = array(
            'operation' => $operation
        );

        $memberId = $this->getMemberId($target);

        if(isset($memberId)) {
            $data['member_id'] = $memberId;
        }

        $this->helper->authenticate($user);

        $response = $this->helper->post('controllers/role_manager.php', null, $data);
        $this->validateResponse($response, 200, 'application/json');

        $content = $response[0];
        $expected = JSON::loadFile($this->getTestFiles()->getFile('role_delegation', $expectedFileName));

        $this->assertEquals($expected, $content);

        $this->helper->logout();
    }

    /**
     * @return array|object
     * @throws \Exception
     */
    public function provideUnauthorizedRoleDelegation()
    {
        return JSON::loadFile(
            $this->getTestFiles()->getFile('role_delegation', 'role_delegation_unauthorized', 'input')
        );
    }

    /**
     * Process the $target and retrieve the appropriate id.
     *
     * @param $target
     *
     * @return int|null|mixed
     *   if target is "null" then null is returned.
     *   if target is found in .secrets.json then retrieve the user id.
     *   else just return target ( to see how components deal with funktastic values )
     */
    private function getMemberId($target)
    {

        if ($target === "null") {
            return null;
        } elseif (array_key_exists($target, $this->config['role'])) {
            return $this->retrieveUserId($this->config['role'][$target]['username'], 1);
        } else {
            return $target;
        }
    }
}
