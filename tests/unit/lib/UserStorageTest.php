<?php

namespace UnitTests;

use UnitTests\TestHelpers\mock\MockXDUser;

class UserStorageTest extends \PHPUnit\Framework\TestCase
{
    public function testGet() {

        $mockuser = new MockXDUser();
        $ustore = new \UserStorage($mockuser, "TEST");

        $output = $ustore->get();

        $this->assertIsArray($output);
        $this->assertCount(0, $output);
    }

    public function testGetExisting() {

        $mockuser = new MockXDUser();
        $ustore = new \UserStorage($mockuser, "TEST");

        $input = array('test' => '1');
        $res = $ustore->insert($input);

        $this->assertArrayHasKey('test', $res);
        $this->assertEquals($res['test'], 1);
        $this->assertArrayHasKey('recordid', $res);

        $output = $ustore->get();

        $this->assertIsArray($output);
        $this->assertCount(1, $output);

        $this->assertArrayHasKey('test', $output[0]);
        $this->assertEquals($output[0]['test'], 1);
        $this->assertEquals($output[0]['test'], 1);

    }

    public function testGetById() {

        $mockuser = new MockXDUser();
        $ustore = new \UserStorage($mockuser, "TEST");

        $input = array('test' => '1');
        $res = $ustore->insert($input);

        $this->assertArrayHasKey('recordid', $res);
        $recordid = $res['recordid'];

        $output = $ustore->getById($recordid);
        $this->assertArrayHasKey('test', $output);

        $output = $ustore->getById($recordid + 1);
        $this->assertEquals(null, $output);
    }

    public function testGetByIdNoExisting() {
        $mockuser = new MockXDUser();
        $ustore = new \UserStorage($mockuser, "TEST");

        $output = $ustore->getById(190);
        $this->assertEquals(null, $output);

        // doubl check that the class still works
        $input = array('test' => '1');
        $res = $ustore->insert($input);
        $this->assertArrayHasKey('test', $ustore->getById($res['recordid']));
    }

    public function testInsertLimits() {
        $mockuser = new MockXDUser();
        $ustore = new \UserStorage($mockuser, "TEST");

        for($i =0; $i < (\UserStorage::MAX_RECORDS + 1); $i++)
        {
            $tmp = array();
            $res = $ustore->insert($tmp);
            if($i < \UserStorage::MAX_RECORDS) {
                $this->assertArrayHasKey('recordid', $res);
            } else {
                $this->assertEquals(null, $res);
            }
        }
    }

    public function testUpsertLimits() {
        $mockuser = new MockXDUser();
        $ustore = new \UserStorage($mockuser, "TEST");

        for($i =0; $i < (\UserStorage::MAX_RECORDS + 1); $i++)
        {
            $tmp = array();
            $res = $ustore->upsert($i, $tmp);
            if($i < \UserStorage::MAX_RECORDS) {
                $this->assertArrayHasKey('recordid', $res);
                $this->assertEquals($res['recordid'], $i);
            } else {
                $this->assertEquals(null, $res);
            }
        }
    }

    private function upsertHelper($ustore, $recordid)
    {
        $tmp = array('value'.$recordid => $recordid);
        $result = $ustore->upsert($recordid, $tmp);
        $this->assertArrayHasKey('recordid', $result);
        $this->assertArrayHasKey('value'.$recordid, $result);
        $this->assertEquals($result['value'.$recordid], $recordid);

        return $result;
    }

    private function insertHelper($ustore)
    {
        $tmp = array('value' => 'stuff');
        $result = $ustore->insert($tmp);
        $this->assertArrayHasKey('recordid', $result);

        $result = $ustore->getById($result['recordid']);

        $this->assertArrayHasKey('value', $result);
        $this->assertArrayHasKey('recordid', $result);

        return $result['recordid'];
    }

    public function testBigUpsert() {
        $mockuser = new MockXDUser();
        $ustore = new \UserStorage($mockuser, "TEST");

        $this->upsertHelper($ustore, 0);
        $this->upsertHelper($ustore, 1);
        $this->upsertHelper($ustore, PHP_INT_MAX);

        $tmp1 = array("value1" => "1");
        $res1 = $ustore->insert($tmp1);
        $this->assertArrayHasKey('recordid', $res1);
        $this->assertNotEquals(0, $res1['recordid']);
        $this->assertNotEquals(1, $res1['recordid']);
        $this->assertNotEquals(PHP_INT_MAX, $res1['recordid']);

        $tmp2 = array("value1" => "1");
        $res2 = $ustore->insert($tmp2);
        $this->assertArrayHasKey('recordid', $res2);
        $this->assertNotEquals(0, $res2['recordid']);
        $this->assertNotEquals(1, $res2['recordid']);
        $this->assertNotEquals(PHP_INT_MAX, $res2['recordid']);
        $this->assertNotEquals($res1['recordid'], $res2['recordid']);
    }

    public function testDelById() {
        $mockuser = new MockXDUser();
        $ustore = new \UserStorage($mockuser, "TEST");

        for($i=0; $i< \UserStorage::MAX_RECORDS; $i++) {
            $this->upsertHelper($ustore, $i);
        }

        for($i=0; $i< \UserStorage::MAX_RECORDS; $i++) {
            $nentries = $ustore->delById($i);
            $this->assertEquals($nentries, \UserStorage::MAX_RECORDS - $i - 1);
        }

        for($i=0; $i< \UserStorage::MAX_RECORDS; $i++) {
            $this->upsertHelper($ustore, $i);
        }

        for($i=\UserStorage::MAX_RECORDS-1; $i >= 0; $i--) {
            $nentries = $ustore->delById($i);
            $this->assertEquals($nentries, $i);
        }
    }

    public function testDelById2() {
        $mockuser = new MockXDUser();
        $ustore = new \UserStorage($mockuser, "TEST");

        $recordids = array();
        for($i=0; $i< \UserStorage::MAX_RECORDS; $i++) {
            $recordids[ "".$this->insertHelper($ustore)] = 1;
        }

        ksort($recordids);
        $this->assertCount(\UserStorage::MAX_RECORDS, $recordids);

        $left = \UserStorage::MAX_RECORDS;
        foreach(array_keys($recordids) as $key) {
            $left -= 1;
            $nentries = $ustore->delById($key);
            $this->assertEquals($nentries, $left);
        }
    }

    public function testDelByIdNoExisting() {
        $mockuser = new MockXDUser();
        $ustore = new \UserStorage($mockuser, "TEST");

        $result = $ustore->delById(1234);
        $this->assertEquals($result, 0);

        $this->upsertHelper($ustore, 123);

        $result = $ustore->delById(1234);
        $this->assertEquals($result, 1);
    }

    public function testDel() {
        $mockuser = new MockXDUser();
        $ustore = new \UserStorage($mockuser, "TEST");

        $recordid = $this->insertHelper($ustore);

        $result = $ustore->del();
        $this->assertEquals(0, $result);

        $result = $ustore->getById($recordid);

        $this->assertEquals(null, $result);
    }

    public function testDelByIdString() {
        $mockuser = new MockXDUser();
        $ustore = new \UserStorage($mockuser, "TEST");

        $this->upsertHelper($ustore, 17);
        $this->upsertHelper($ustore, 3);
        $this->upsertHelper($ustore, "43");
        $this->upsertHelper($ustore, 67);

        $this->assertEquals(3, $ustore->delById("67"));
        $this->assertEquals(2, $ustore->delById("3"));

        $this->assertEquals(44, $this->insertHelper($ustore));
    }
}
