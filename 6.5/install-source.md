---
title: Source Installation Guide
---

Install Source Package
----------------------

    $ tar zxvf xdmod-x.y.z.tar.gz
    $ cd xdmod-x.y.z
    # ./install --prefix=/opt/xdmod

Change the prefix as desired.  The default installation prefix is
`/usr/local/xdmod`.  These instructions assume you are installing Open
XDMoD in `/opt/xdmod`.

Run Configuration Script
------------------------

    # /opt/xdmod/bin/xdmod-setup

Complete each setup section with the required information.

See the [Configuration Guide](configuration.html) for more details.

Copy Configuration Files
------------------------

    # cp /opt/xdmod/etc/apache.d/xdmod.conf /etc/apache2/conf.d/xdmod.conf

    # cp /opt/xdmod/etc/cron.d/xdmod /etc/cron.d/xdmod

    # cp /opt/xdmod/etc/logrotate.d/xdmod /etc/logrotate.d/xdmod

The directories where these files are needed may differ depending on
your operating system.  By default, the Apache configuration creates a
virtual host on port 8080.

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

    $ /opt/xdmod/bin/xdmod-shredder -v -r *resource* -f pbs \
          -d /var/spool/pbs/server_priv/accounting

SGE stores its accounting logs in a single file so the `-i` (input)
option should be used:

    $ /opt/xdmod/bin/xdmod-shredder -v -r *resource* -f sge \
          -i /var/lib/gridengine/default/common/accounting

Slurm stores its accounting logs in a database that can be queried using
the `sacct` command.  Since the accounting data is not directly
accessible in files Open XDMoD provides a helper script that writes the
data to a file and then shreds that file:

    $ /opt/xdmod/bin/xdmod-slurm-helper -v -r *resource*

The resource name here must match the one supplied to the setup script.

**NOTE**: This command only works if Open XDMoD is on a machine with the
`sacct` command or if you have configured your `portal_settings.ini`
file with a command that accepts the same arguments as `sacct` (such as
using `ssh` with a public key to run the command on another machine).

Ingest Data
-----------

    $ /opt/xdmod/bin/xdmod-ingestor -v

See the [Ingestor Guide](ingestor.html) for more details.

Restart Apache
--------------

    # /etc/init.d/apache2 restart

This command may be different depending on your operating system.

Check Portal
------------

    http://localhost:8080/
