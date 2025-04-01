---
title: Source Installation Guide
---

Install Source Package
----------------------

The source package can be downloaded from
[GitHub](https://github.com/ubccr/xdmod/releases/tag/v{{ page.rpm_version }}).
Make sure to download `xdmod-{{ page.sw_version }}.tar.gz`, not the
GitHub-generated "Source code" files.

These instructions assume you are installing Open XDMoD in `/opt/xdmod-{{
page.sw_version }}`. Change the installation prefix as desired. The default
installation prefix is `/usr/local/xdmod`.

    # tar zxvf xdmod-{{ page.sw_version }}.tar.gz
    # cd xdmod-{{ page.sw_version }}
    # ./install --prefix=/opt/xdmod-{{ page.sw_version }}

Create Open XDMoD User And Group
--------------------------------

For improved security, create an Open XDMoD user and group that will have
access to sensitive data:

    # groupadd -r xdmod
    # useradd -r -M -c "Open XDMoD" -g xdmod -d /opt/xdmod-{{ page.sw_version }}/lib -s /sbin/nologin xdmod

Secure the file containing database passwords:

    # chmod 440 /opt/xdmod-{{ page.sw_version }}/etc/portal_settings.ini
    # chown apache:xdmod /opt/xdmod-{{ page.sw_version }}/etc/portal_settings.ini

**NOTE**: The `portal_settings.ini` file must be readable by Apache and any
user that will run the Open XDMoD commands.  Replace the Apache username
(`apache`) with the appropriate name if it is different on your system.

Add any user that will run Open XDMoD to the `xdmod` group:

    # usermod -a -G xdmod jdoe

Replace `jdoe` with an appropriate username.  Typically, you will need to log
out of your system and log back in for this change to take effect.

Update log directory and file ownership and permissions:

    # chmod 770 /opt/xdmod-{{ page.sw_version }}/logs
    # chown apache:xdmod /opt/xdmod-{{ page.sw_version }}/logs
    # touch /opt/xdmod-{{ page.sw_version }}/logs/exceptions.log
    # chmod 660 /opt/xdmod-{{ page.sw_version }}/logs/exceptions.log
    # chown apache:xdmod /opt/xdmod-{{ page.sw_version }}/logs/exceptions.log
    # touch /opt/xdmod-{{ page.sw_version }}/logs/query.log
    # chmod 660 /opt/xdmod-{{ page.sw_version }}/logs/query.log
    # chown apache:xdmod /opt/xdmod-{{ page.sw_version }}/logs/query.log

The `exceptions.log` and `query.log` may be written to by both Apache and Open
XDMoD commands.

Update the bin directory permissions so that only users with the `xdmod` group can run Open XDMoD commands:

    # chmod 750 /opt/xdmod-{{ page.sw_version }}/bin/*

Run Configuration Script
------------------------

    # /opt/xdmod-{{ page.sw_version }}/bin/xdmod-setup

Complete each setup section with the required information.

See the [Configuration Guide](configuration.html) for more details.

Copy Configuration Files
------------------------

    # cp /opt/xdmod-{{ page.sw_version }}/share/templates/apache.conf /etc/apache2/conf.d/xdmod.conf

    # cp /opt/xdmod-{{ page.sw_version }}/etc/cron.d/xdmod /etc/cron.d/xdmod

    # cp /opt/xdmod-{{ page.sw_version }}/etc/logrotate.d/xdmod /etc/logrotate.d/xdmod

The directories where these files are needed may differ depending on
your operating system.

The Apache configuration file is an example template. This template will need
to be edited to specify site-specific parameters such as the SSL certificate
paths and server name.  See the [Apache Configuration
Guide](configuration.html#apache-configuration) for details.

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

    $ /opt/xdmod-{{ page.sw_version }}/bin/xdmod-shredder -v -r *resource* -f pbs \
          -d /var/spool/pbs/server_priv/accounting

SGE stores its accounting logs in a single file so the `-i` (input)
option should be used:

    $ /opt/xdmod-{{ page.sw_version }}/bin/xdmod-shredder -v -r *resource* -f sge \
          -i /var/lib/gridengine/default/common/accounting

Slurm stores its accounting logs in a database that can be queried using
the `sacct` command.  Since the accounting data is not directly
accessible in files Open XDMoD provides a helper script that writes the
data to a file and then shreds that file:

    $ /opt/xdmod-{{ page.sw_version }}/bin/xdmod-slurm-helper -v -r *resource*

The resource name here must match the one supplied to the setup script.

**NOTE**: This command only works if Open XDMoD is on a machine with the
`sacct` command or if you have configured your `portal_settings.ini`
file with a command that accepts the same arguments as `sacct` (such as
using `ssh` with a public key to run the command on another machine).

Ingest Data
-----------

    $ /opt/xdmod-{{ page.sw_version }}/bin/xdmod-ingestor -v

See the [Ingestor Guide](ingestor.html) for more details.

Restart Apache
--------------

    # systemctl restart httpd.service

This command may be different depending on your operating system.

Check Portal
------------

    https://localhost/
