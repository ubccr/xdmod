<?php

namespace ComponentTests\DB;

class EtlJournalTest extends BaseTest
{
    public function testJournal()
    {
        $table_name = 'journaltest_' . uniqid();
        $db = \CCR\DB::factory('datawarehouse');
        $db->execute("DROP TABLE IF EXISTS `$table_name`");
        $db->execute("CREATE TABLE `$table_name` (`id` INT(11), `last_modified` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp())");
        $db->execute("INSERT INTO `$table_name` (id) VALUES(1)");
        $ref_data = $db->query("SELECT FROM_UNIXTIME(UNIX_TIMESTAMP(last_modified) + 1) AS lm FROM `$table_name` WHERE id = 1");

        $helper = new \DB\EtlJournalHelper('modw', $table_name);

        $last_modified_1 = $helper->getLastModified();

        $this->assertNull($last_modified_1);

        $helper->markasDone('2025-01-01', '2025-01-31');

        $last_modified_2 = $helper->getLastModified();

        $this->assertEquals($ref_data[0]['lm'], $last_modified_2);

        $db->execute("DROP TABLE `$table_name`");
    }
}
