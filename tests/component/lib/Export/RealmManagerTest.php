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
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

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
     * @param \Model\Realm $realm
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
     * @dataProvider getRealmsProvider
     */
    public function testGetRealms($realms)
    {
        $this->assertEquals(
            $realms,
            array_map(
                [$this, 'convertRealmToArray'],
                self::$realmManager->getRealms()
            ),
            'getRealms returns expected realms'
        );
    }

    /**
     * Test which realms may be exported for a given user.
     *
     * @covers ::getRealmsForUser
     * @dataProvider getRealmsForUserProvider
     */
    public function testGetRealmsForUser($role, $realms)
    {
        $this->assertEquals(
            $realms,
            array_map(
                [$this, 'convertRealmToArray'],
                self::$realmManager->getRealmsForUser(self::$users[$role])
            ),
            "getRealmsForUser returns expected realms for role $role"
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

    public function getRealmsProvider()
    {
        return $this->getTestFiles()->loadJsonFile(self::TEST_GROUP, 'realms', 'output');
    }

    public function getRealmsForUserProvider()
    {
        return $this->getTestFiles()->loadJsonFile(self::TEST_GROUP, 'realms-for-user', 'output');
    }

    public function getRawDataQueryClassProvider()
    {
        return $this->getTestFiles()->loadJsonFile(self::TEST_GROUP, 'query-class-for-realm', 'output');
    }
}
