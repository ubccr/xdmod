---
title: Shredder Guide
---

This guide will attempt to outline the use of the Open XDMoD shredder
command line utility.  The shredder is responsible for loading data from
resource manager log files into the Open XDMoD databases.  If you are
running Slurm, you should first read the
[Slurm Notes](resource-manager-slurm.html).

General Usage
-------------

In order to make data available to the Open XDMoD portal you will need
to use the shredder utility. If you followed the install guide, you will
have already used the shredder to populate your database. In addition to
the install process, this program is typically used once a day to add
jobs from the previous day to the database.

Help
----

To display the shredder help text from the command line:

    $ xdmod-shredder -h

Verbose Output
--------------

By default the Open XDMoD shredder only outputs what it considers to be
warnings or errors. If you would like to see informational output about
what is being performed, use the verbose option:

    $ xdmod-shredder -v ...

Debugging output is also available:

    $ xdmod-shredder --debug ...

Resource Name
-------------

You must specify a resource name (the name of your cluster) during the
shredding process.  This name must match the name that you provided
during the setup process.  When using Slurm this name must match the
cluster name.

    $ xdmod-shredder -r mycluster ...

Log Format
----------

You must specify the format of the log files to be shredded.  For HPC job
accounting data, the format depends upon the resource manager.  For cloud data
the format should match that of the event logs.  There is only one supported
format for storage data.

**Jobs:**

For [TORQUE and OpenPBS][pbs] use `pbs`, for [Sun Grid Engine][sge] use
`sge`, for [Univa Grid Engine 8.2+][uge] use `uge`, for [Slurm][] use
`slurm` and for [LSF][] use `lsf`.

    $ xdmod-shredder -f pbs ...
    $ xdmod-shredder -f sge ...
    $ xdmod-shredder -f uge ...
    $ xdmod-shredder -f slurm ...
    $ xdmod-shredder -f lsf ...

[pbs]:   resource-manager-pbs.md
[sge]:   resource-manager-sge.md
[uge]:   resource-manager-uge.md
[slurm]: resource-manager-slurm.md
[lsf]:   resource-manager-lsf.md

**Cloud:**

The shredder accepts two different types of cloud data, `genericcloud` and `openstack`.
The convention for shredding cloud files is identical to job data:

    $ xdmod-shredder -f genericcloud ...
    $ xdmod-shredder -f openstack ...

**Storage:**

The shredder accepts one format for storage data.  See the [Storage
Metrics](storage.md) documentation for an example.  The convention for
shredding storage files is identical to job data:

    $ xdmod-shredder -f storage ...

Input Source
------------

Files may be shredded one at a time by running the following command.  Please
note that this is **not** currently supported for cloud and storage files:

    $ xdmod-shredder -i file ...

An entire directory of files can be shredded, but the names of the files
must be formatted as `YYYYMMDD` (e.g. 20120101).

The log file for the current day will be ignored (along with any files
that correspond to future dates). This is intended to prevent the
shredding of partial log files.

If the database is empty all files that meet the above constraints will
be shredded. If there is data in the database, only files dated after
the date of the most recent job will be shredded.

    $ xdmod-shredder -d directory ...
