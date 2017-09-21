---
title: RPM Installation Guide
---

Install Prerequisites
---------------------

See the [Software Requirements](software-requirements.html) for details.

Install the RPM
---------------

    # yum install xdmod-x.x.x-x.x.el6.noarch.rpm

Configure Open XDMoD
--------------------

Be sure MySQL is running before using the setup command.

    # xdmod-setup

See the [Configuration Guide](configuration.html) for more details.

Shred Data
----------

Depending on which resource manager you use, your accounting logs may
be stored in a single file, a directory containing multiple files or
in a database that is queried using a specific command.  Each of these
is handled with different options in Open XDMoD.

See the [Shredder Guide](shredder.html) and the resource manager notes for
more details.

PBS stores its accounting logs in a directory where the name of each
file corresponds to the end date of the jobs it contains.  A directory
such as this can be specified using the `-d` option:

    $ xdmod-shredder -v -r *resource* -f pbs \
          -d /var/spool/pbs/server_priv/accounting

SGE stores its accounting logs in a single file so the `-i` (input)
option should be used:

    $ xdmod-shredder -v -r *resource* -f sge \
          -i /var/lib/gridengine/default/common/accounting

Slurm stores its accounting logs in a database that can be queried using
the `sacct` command.  Since the accounting data is not directly
accessible in files Open XDMoD provides a helper script that writes the
data to a file and then shreds that file:

    $ xdmod-slurm-helper -v -r *resource*

The resource name here must match the one supplied to the setup script.

**NOTE**: This command only works if Open XDMoD is on a machine with the
`sacct` command or if you have configured your `portal_settings.ini`
file with a command that accepts the same arguments as `sacct` (such as
using `ssh` with a public key to run the command on another machine).

Ingest Data
-----------

See the [Ingestor Guide](ingestor.html) for more details.

Reload Apache
-------------

    # service httpd reload

Now you should be able to view the Open XDMoD portal at the URL used
during the configuration process.
