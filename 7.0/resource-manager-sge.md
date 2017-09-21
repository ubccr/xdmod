---
title: Sun Grid Engine (SGE) Notes
---

Open XDMoD supports Sun Grid Engine with the caveats listed below.  SGE
support includes Univa Grid Engine (pre-8.2) and the other SGE
derivatives that use the same accounting log format as Sun Grid Engine.

**NOTE**: If you are using Univa Grid Engine 8.2+ you should use the
[Univa Grid Engine](resource-manager-uge.html) Shredder.

Nodes and CPUs
--------------

SGE does not report the nodes used by a given job in the accounting log.
For accurate node counts the `accounting_summary` must be set to `FALSE`
in `sge_pe`.  If `accounting_summary` is set to `TRUE` it will appear
that all jobs were run on a single node.

Open XDMoD supports two different ways of specifying the number of CPUs
used by a job. By default, the number of `slots` reported by SGE will be
used as the number of CPUs. If you configure `num_procs` as a consumable
resource, the greater of the two values will be used as the number of
CPUs.

Log Files
---------

If your logs are not rotated (you re-shred the same log file every time
you update the Open XDMoD database), when Open XDMoD inserts duplicate
data, the unique key constraint on the `shredded_job_sge` table will
prevent the insertion of this data, but will instead update the primary
key. This will prevent duplicate data from being entered into the
database, even though the entire log file will still be parsed.

Unsupported Shredder Features
-----------------------------

The xdmod-shredder `-d`/`--dir` option was designed to work with the
accounting log naming convention used by PBS/TORQUE. If you are not
using the same convention (files are named `YYYYMMDD` corresponding to
the current date), do not use this option.
