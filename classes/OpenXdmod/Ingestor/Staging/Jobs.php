<?php

namespace OpenXdmod\Ingestor\Staging;

use PDODBMultiIngestor;

class Jobs extends PDODBMultiIngestor
{
    public function __construct($dest_db, $src_db)
    {
        $src_query = "
            SELECT
                j.id                          AS job_id,
                uup.union_user_pi_id          AS person_id,
                r.resource_id                 AS resource_id,
                upr.user_pi_resource_id       AS allocation_breakdown_id,
                pr.pi_resource_id             AS allocation_id,
                p.pi_id                       AS account_id,
                j.job_id                      AS local_jobid,
                IFNULL(j.job_array_index, -1) AS local_job_array_index,
                j.job_id_raw                  AS local_job_id_raw,
                j.job_name                    AS jobname,
                j.queue_name                  AS queue,
                j.user_name                   AS username,
                j.uid_number                  AS uid_number,
                j.group_name                  AS groupname,
                j.gid_number                  AS gid_number,
                j.start_time                  AS start_time,
                j.end_time                    AS end_time,
                j.submission_time             AS submit_time,
                j.eligible_time               AS eligible_time,
                j.wall_time                   AS wallduration,
                j.exit_code                   AS exit_code,
                j.exit_state                  AS exit_state,
                j.node_count                  AS nodecount,
                j.cpu_count                   AS processors,
                j.cpu_req                     AS cpu_req,
                j.mem_req                     AS mem_req,
                j.timelimit                   AS timelimit,
                j.node_list                   AS node_list,
                UNIX_TIMESTAMP()              AS ts
            FROM staging_job j
            LEFT JOIN staging_union_user_pi uup
                ON j.user_name = uup.union_user_pi_name
            LEFT JOIN staging_pi p
                ON j.pi_name = p.pi_name
            LEFT JOIN staging_resource r
                ON j.resource_name = r.resource_name
            LEFT JOIN staging_pi_resource pr
                 ON j.pi_name       = pr.pi_name
                AND j.resource_name = pr.resource_name
            LEFT JOIN staging_user_pi_resource upr
                 ON j.user_name     = upr.user_name
                AND j.pi_name       = upr.pi_name
                AND j.resource_name = upr.resource_name
        ";

        $sql = 'SELECT MAX(job_id) AS max_id FROM hpcdb_jobs';
        list($row) = $dest_db->query($sql);
        if ($row['max_id'] != null) {
            $src_query .= 'WHERE j.id > ' . $row['max_id'];
        }

        parent::__construct(
            $dest_db,
            $src_db,
            array(),
            $src_query,
            'hpcdb_jobs',
            array(
                'job_id',
                'person_id',
                'resource_id',
                'allocation_breakdown_id',
                'allocation_id',
                'account_id',
                'local_jobid',
                'local_job_array_index',
                'local_job_id_raw',
                'jobname',
                'queue',
                'username',
                'uid_number',
                'groupname',
                'gid_number',
                'start_time',
                'end_time',
                'submit_time',
                'eligible_time',
                'wallduration',
                'exit_code',
                'exit_state',
                'nodecount',
                'processors',
                'cpu_req',
                'mem_req',
                'timelimit',
                'node_list',
                'ts',
            ),
            array(),
            'nodelete'
        );
    }
}
