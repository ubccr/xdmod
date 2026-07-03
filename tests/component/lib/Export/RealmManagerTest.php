<?php

namespace ComponentTests\Export;

use CCR\Json;
use ComponentTests\BaseTest;
use DataWarehouse\Export\RealmManager;
use Models\Realm;
use XDUser;

/**
 * Test data warehouse export realm management.
 *
 * @coversDefaultClass \DataWarehouse\Export\RealmManager
 */
class RealmManagerTest extends BaseTest
{
    /**
     * Test artifacts path.
     */
    const TEST_GROUP = 'component/export/realm_manager';

    /**
     * @var \DataWarehouse\Export\RealmManager
     */
    private static $realmManager;

    /**
      * User roles and usernames.
      * @var string[]
      */
    private static $userRoles = [
      'pub' => 'Public User',
      'usr' => 'normaluser',
      'pi' => 'principal',
      'cs' => 'centerstaff',
      'cd' => 'centerdirector',
      'mgr' => 'admin'
    ];

    /**
     * User for each role.
     * @var XDUser[]
     */
    private static $users = [];

    /**
     */
    public static function setupBeforeClass(): void
    {
        parent::setupBeforeClass();

        self::$realmManager = new RealmManager();

        foreach (self::$userRoles as $role => $username) {
            //if ($role !== 'pub') {
                self::$users[$role] = XDUser::getUserByUserName($username);
            //}
        }
    }

    /**
     * Convert a realm model object to an array.
     *
     * Only includes the relevant properties from the realm that are used by
     * the realm manager class.
     *
     * @param \Models\Realm $realm
     * @return array
     */
    private function convertRealmToArray(Realm $realm)
    {
        return [
            'name' => $realm->getName(),
            'display' => $realm->getDisplay()
        ];
    }

    /**
     * Test which realms may be exported.
     *
     * @covers ::getRealms
     */
    public function testGetRealms()
    {
        $this->runGetRealmsTest(self::$realmManager->getRealms());
    }

    /**
     * Test which realms may be exported for a given user.
     *
     * @covers ::getRealmsForUser
     */
    public function testGetRealmsForUser()
    {
        $this->runGetRealmsTest(self::$realmManager->getRealmsForUser(self::$users['usr']));
    }

    private function runGetRealmsTest($actualRealms)
    {
        $expected = [];
        $i = 0;
        foreach (['Jobs', 'Cloud', 'ResourceSpecifications'] as $realm) {
            if (in_array(strtolower($realm), self::$XDMOD_REALMS)) {
                $expected[(string)($i++)] = [
                    'name' => $realm,
                    'display' => (
                        $realm == 'ResourceSpecifications'
                        ? 'Resource Specifications'
                        : $realm
                    )
                ];
            }
        }
        $actual = array_map(
            fn($realm) => ['name' => $realm->getName(), 'display' => $realm->getDisplay()],
            $actualRealms
        );
        $this->assertEquals(
            $expected,
            $actual,
            sprintf('Expected: %s, Received: %s', json_encode($expected), json_encode($actual))
        );
    }

    /**
     * Test what query class should be used for each realm.
     *
     * @covers ::getRawDataQueryClass
     * @dataProvider getRawDataQueryClassProvider
     */
    public function testGetRawDataQueryClassProvider($realmName, $queryClassName)
    {
        $this->assertEquals(
            $queryClassName,
            self::$realmManager->getRawDataQueryClass($realmName),
            "getRawDataQueryClass returns expected query class for realm $realmName"
        );
    }

    public function getRawDataQueryClassProvider()
    {
        return $this->getTestFiles()->loadJsonFile(self::TEST_GROUP, 'query-class-for-realm', 'output');
    }
}
