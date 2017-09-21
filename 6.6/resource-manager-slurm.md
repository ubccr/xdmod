---
title: Slurm Notes
---

Helper Script
-------------

Open XDMoD includes a helper script to pull data from Slurm's `sacct`
into Open XDMoD's shredder system. This script can be used in place of
the shredder to import data. To shred data for all Slurm clusters, use
this command:

    $ xdmod-slurm-helper

If you have multiple Slurm clusters, but only want to shred data from
one of them, then use this command with the name of a single cluster
that would be used with `sacct`'s `--clusters` option:

    $ xdmod-slurm-helper -r mycluster

The helper script doesn't update the aggregate tables, so that must be
done after the data has been shredded:

    $ xdmod-ingestor

If your `sacct` executable isn't in the `PATH` of the user that will be
running the `xdmod-slurm-helper` command, you can specify the path by
modifying the following section in your `portal_settings.ini` file.

    [slurm]
    sacct = "/path/to/sacct"

Use this command to display the help text for the Slurm helper script:

    $ xdmod-slurm-helper -h

Input Format
------------

If you'd prefer to not use the helper script, you can export data from
Slurm into a file manually using the `sacct` command and then shred that
file.  The format must be the same as below.  Also, the `--parsable2`,
`--noheader` and `--allocations` are all required.  Replace `*cluster*`
with the name of your resource.  It may also be possible to use other
options that limit the output.

    $ TZ=UTC sacct --clusters *cluster* --allusers \
        --parsable2 --noheader --allocations \
        --format jobid,jobidraw,cluster,partition,account,group,gid,\
    user,uid,submit,eligible,start,end,elapsed,exitcode,state,nnodes,\
    ncpus,reqcpus,reqmem,reqgres,reqtres,timelimit,nodelist,jobname \
        --state CANCELLED,COMPLETED,FAILED,NODE_FAIL,PREEMPTED,TIMEOUT \
        --starttime 2013-01-01T00:00:00 --endtime 2013-01-01T23:59:59 \
        >/tmp/slurm.log

    $ xdmod-shredder -r *cluster* -f slurm -i /tmp/slurm.log

**NOTE**: The time zone used in the output from `sacct` must be UTC to
prevent ambiguities caused by clock changes due to daylight savings. The
shredder will assume input times are in UTC regardless of your system
time zone.

Unsupported Shredder Features
-----------------------------

The `xdmod-shredder` `-d`/`--dir` option was designed to work with the
accounting log naming convention used by PBS/TORQUE. If you are not
using the same convention (files are named `YYYYMMDD` corresponding to
the date jobs ended), do not use this option.
