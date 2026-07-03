<?php

namespace IntegrationTests\Database;

use IntegrationTests\BaseTest;
use CCR\DB;
use CCR\DB\MySQLHelper;

class DatabaseTest extends BaseTest
{
    const EXPORT_REQUEST_TABLE_NAME = 'batch_export_requests';

    private static $RESOURCES;

    public static function setupBeforeClass(): void
    {
        parent::setupBeforeClass();
        self::$RESOURCES = [
            'cloud' => ['OpenStack'],
            'jobs' => ['Frearson', 'Mortorq', 'Phillips', 'Posidriv', 'Robertson'],
            'storage' => ['Recex', 'Torx']
        ];
        self::$RESOURCES['gateways'] = self::$RESOURCES['jobs'];
        self::$RESOURCES['resourcespecifications'] = self::$RESOURCES['jobs'];
        if (in_array('cloud', self::$XDMOD_REALMS)) {
            self::$RESOURCES['resourcespecifications'] = array_merge(
                self::$RESOURCES['resourcespecifications'],
                self::$RESOURCES['cloud']
            );
        }
    }

    /**
     * @dataProvider databaseTestProvider
     */
    public function testDatabase($query, $resourceNameFn)
    {
        $actual = DB::factory('datawarehouse')->query($query);
        foreach (self::$XDMOD_REALMS as $realm) {
            foreach (self::$RESOURCES[$realm] as $name) {
                $this->assertContains($resourceNameFn($name), $actual);
            }
        }
    }

    public function databaseTestProvider()
    {
        $getCodeFromName = fn ($name) => (
            $name == 'Posidriv' ? 'pozidriv' : strtolower($name)
        );
        return [
            [
                'SELECT name, code FROM modw.resourcefact ORDER BY code',
                fn ($name) => [
                    'name' => $name,
                    'code' => $getCodeFromName($name)
                ]
            ],
            [
                'SELECT code, shared_jobs FROM modw.resourcefact ORDER BY code',
                fn ($name) => [
                    'code' => $getCodeFromName($name),
                    'shared_jobs' => '0'
                ]
            ]
        ];
    }

    /**
     * Test that the table used by the data warehouse export exists.
     */
    public function testExportRequestTableExists()
    {
        $this->assertTrue(
            MySQLHelper::factory(DB::factory('database'))->tableExists(self::EXPORT_REQUEST_TABLE_NAME),
            sprintf('Table `%s` exists', self::EXPORT_REQUEST_TABLE_NAME)
        );
    }

    /**
     * Test that the table used by the data warehouse export is empty.
     *
     * @depends testExportRequestTableExists
     */
    public function testExportRequestTableEmpty()
    {
        list($row) = DB::factory('database')->query(
            sprintf(
                'SELECT COUNT(*) AS count FROM `%s`',
                self::EXPORT_REQUEST_TABLE_NAME
            )
        );
        $this->assertEquals(
            0,
            $row['count'],
            sprintf('Table `%s` is empty', self::EXPORT_REQUEST_TABLE_NAME)
        );
    }
}
