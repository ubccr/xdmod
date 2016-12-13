<?php

namespace OpenXdmod\Ingestor\Shredded;

use PDODBMultiIngestor;

class Jobs extends PDODBMultiIngestor
{
    public function __construct($dest_db, $src_db)
    {
        $src_query = '
            SELECT
                shredded_job_id AS id,
                job_id,
                job_array_index,
                job_id_raw,
                job_name,
                resource_name,
                queue_name,
                user_name,
                uid_number,
                group_name,
                gid_number,
                pi_name,
                account_name,
                project_name,
                start_time,
                end_time,
                submission_time,
                eligible_time,
                wall_time,
                wait_time,
                exit_code,
                exit_state,
                node_count,
                cpu_count,
                cpu_req,
                mem_req,
                timelimit,
                node_list
            FROM shredded_job
        ';

        $sql = 'SELECT MAX(id) AS max_id FROM staging_job';
        list($row) = $dest_db->query($sql);
        if ($row['max_id'] != null) {
            $src_query .= 'WHERE shredded_job_id > ' . $row['max_id'];
        }

        parent::__construct(
            $dest_db,
            $src_db,
            array(),
            $src_query,
            'staging_job',
            array(
                'id',
                'job_id',
                'job_array_index',
                'job_id_raw',
                'job_name',
                'resource_name',
                'queue_name',
                'user_name',
                'uid_number',
                'group_name',
                'gid_number',
                'pi_name',
                'account_name',
                'project_name',
                'start_time',
                'end_time',
                'submission_time',
                'eligible_time',
                'wall_time',
                'wait_time',
                'exit_code',
                'exit_state',
                'node_count',
                'cpu_count',
                'cpu_req',
                'mem_req',
                'timelimit',
                'node_list',
            ),
            array(),
            'nodelete'
        );
    }
}
