---
title: HOWTO Reconstruct Slurm accounting data
---

In some situations historical accounting data is only available in an instance of XDMoD. It is possible to reconstruct the Slurm accounting logs for the Jobs realm from XDMoD.

Edit the following query to output reconstructed accounting data from `sacct`.
Be sure to change the output filename, time range start and end, and cluster name.
```sql
SET time_zone = '+0:00';

USE mod_shredder;

DROP FUNCTION IF EXISTS slurm_format;

DELIMITER $$
CREATE FUNCTION slurm_format(time INT)
RETURNS TEXT
BEGIN
    DECLARE days,hours,minutes,seconds INT;
    SET days = time DIV (60 * 60 * 24);
    SET time = time % (60 * 60 * 24);
    SET hours = time DIV (60 * 60);
    SET time = time % (60 * 60);
    SET minutes = time DIV 60;
    SET time = time % 60;
    SET seconds = time;
    RETURN CONCAT(days, '-', LPAD(hours, 2, 0), ':', LPAD(minutes, 2, 0), ':', LPAD(seconds, 2, 0));
END;
$$
DELIMITER ;

SELECT IF(job_array_index = -1, job_id, CONCAT(job_id, '_', job_array_index))      as job_id,
       job_id_raw                                                                  as job_id_raw,
       cluster_name                                                                as cluster,
       partition_name                                                              as partition,
       qos_name                                                                    as qos,
       account_name                                                                as account,
       group_name                                                                  as group,
       gid_number                                                                  as gid,
       user_name                                                                   as user,
       uid_number                                                                  as uid,
       FROM_UNIXTIME(submit_time, '%Y-%m-%dT%H:%i:%s')                             as submit,
       FROM_UNIXTIME(eligible_time, '%Y-%m-%dT%H:%i:%s')                           as eligible,
       FROM_UNIXTIME(start_time, '%Y-%m-%dT%H:%i:%s')                              as start,
       FROM_UNIXTIME(end_time, '%Y-%m-%dT%H:%i:%s')                                as end,
       slurm_format(elapsed)                                                       as elapsed,
       exit_code                                                                   as exitcode,
       state                                                                       as state,
       nnodes                                                                      as nnodes,
       ncpus                                                                       as ncpus,
       req_cpus                                                                    as reqcpus,
       req_mem                                                                     as reqmem,
       req_tres                                                                    as reqtres,
       alloc_tres                                                                  as alloctres,
       slurm_format(timelimit)                                                     as timelimit,
       node_list                                                                   as nodelist,
       job_name                                                                    as jobname
INTO OUTFILE 'YOUR_FILENAME.txt'
FIELDS TERMINATED BY '|'
LINES TERMINATED BY '\n'
FROM shredded_job_slurm
WHERE start_time >= UNIX_TIMESTAMP('YYYY-MM-DD 00:00:00') AND start_time <= UNIX_TIMESTAMP('YYYY-MM-DD 23:59:59')

AND cluster_name = 'YOUR_CLUSTER_NAME';
```

Assuming the above text has been copied into a file `clustername.sql`, the following command can be run to obtain the reconstructed accounting data in `clustername.txt`:

`$ mysql -u xdmod -p < clustername.sql`

Unless another path is specified, the file `clustername.txt` should now be in the `mod_shredder` directory. By default this will be `/var/lib/mysql/mod_shredder/` but is configuration dependent.

The data now can be shredded and ingested into another instance of XDMoD. Given the output `clustername.txt` from above:
```
# xdmod-shredder -r clustername -f slurm -i clustername.txt
# xdmod-ingestor
```
