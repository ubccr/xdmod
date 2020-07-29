---
title: Upgrade Guide
---

General Upgrade Notes
---------------------

- Open XDMoD only supports upgrading to a new version from the version that
  directly precedes it unless otherwise noted below.  If you need to upgrade
  from an older version you must upgrade through all the intermediate versions
  or perform a clean installation.
- Make a backup of your Open XDMoD configuration files before running
  the upgrade script.  The upgrade script may overwrite your current
  configuration files.
- If the upgrade includes database schema changes (see notes at the
  bottom of this page), you should backup all your data.
- Do not change the version in `portal_settings.ini` before running the
  upgrade script.  The version number will be changed by the upgrade
  script.
- If you have installed any additional Open XDMoD packages (e.g.
  `xdmod-appkernels` or `xdmod-supremm`), upgrade those to the latest
  version before running `xdmod-upgrade`.

RPM Upgrade Process
-------------------

The Open XDMoD 9.0 RPM no longer puts the example Apache configuration
into the Apache configuration directory. Instead an example configuration
is provided in the `/usr/share/xdmod/templates` directory.
After upgrading the RPM, you will need to manually update your Apache
config file following the [Apache configuration](configuration.html#apache-configuration) instructions.

If you have edited the previous Apache config file (`/etc/httpd/conf.d/xdmod.conf`) then
it will be saved in `/etc/httpd/conf.d/xdmod.conf.rpmsave`. If the Open XDMoD instance was
using the original configuration unmodified then the old file will be removed.

### Download Latest Open XDMoD RPM package

Download available at [GitHub][github-latest-release].

### Install the RPM

    # yum install xdmod-{{ page.sw_version }}-1.0.el7.noarch.rpm

Likewise, install the latest `xdmod-appkernels` or `xdmod-supremm` RPM
files if you have those installed.

After upgrading the package you may need to manually merge any files
that you have manually changed before the upgrade.  You do not need to
merge `portal_settings.ini`.  This file will be updated by the upgrade
script.  If you have manually edited this file, you should create a
backup and merge any changes after running the upgrade script.

### Verify Server Configuration Settings

Double check that the MySQL server configuration settings are consistent with
the recommended values listed on the [software requirements page][mysql-config].

### Upgrade Database Schema and Config Files

    # xdmod-upgrade

Source Package Upgrade Process
------------------------------

This example assumes that your previous version of Open XDMoD is installed at
`/opt/xdmod-{{ page.prev_sw_version }}` and the new version of Open XDMoD will be installed at
`/opt/xdmod-{{ page.sw_version }}`.  It is recommended to install the new version of Open XDMoD
in a different directory than your existing version.

### Download Latest Open XDMoD Source Package

Download available at [GitHub][github-latest-release].

### Extract and Install Source Package

    $ tar zxvf xdmod-{{ page.sw_version }}.tar.gz
    $ cd xdmod-{{ page.sw_version }}
    # ./install --prefix=/opt/xdmod-{{ page.sw_version }}

Likewise, install the latest `xdmod-appkernels` or `xdmod-supremm`
tarballs if you have those installed.

### Copy Current Config Files

    # cp /opt/xdmod-{{ page.prev_sw_version }}/etc/portal_settings.ini /opt/xdmod-{{ page.sw_version }}/etc
    # cp /opt/xdmod-{{ page.prev_sw_version }}/etc/hierarchy.json      /opt/xdmod-{{ page.sw_version }}/etc
    # cp /opt/xdmod-{{ page.prev_sw_version }}/etc/organization.json   /opt/xdmod-{{ page.sw_version }}/etc
    # cp /opt/xdmod-{{ page.prev_sw_version }}/etc/resource_specs.json /opt/xdmod-{{ page.sw_version }}/etc
    # cp /opt/xdmod-{{ page.prev_sw_version }}/etc/resources.json      /opt/xdmod-{{ page.sw_version }}/etc
    # cp /opt/xdmod-{{ page.prev_sw_version }}/etc/update_check.json   /opt/xdmod-{{ page.sw_version }}/etc

If you have manually changed (i.e. not using `xdmod-setup`) any of the
other config files you may need to merge your changes into the new
config files.  You should `diff` the config files to see what has
changed in the new version.  You do not need to merge
`portal_settings.ini`.  This file will be updated by the upgrade script.
If you have manually edited this file, you should create a backup and
merge any changes after running the upgrade script.

### Verify Server Configuration Settings

Double check that the MySQL server configuration settings are consistent with
the recommended values listed on the [software requirements page][mysql-config].

### Upgrade Database Schema and Config Files

    # /opt/xdmod-{{ page.sw_version }}/bin/xdmod-upgrade

9.0.0 Upgrade Notes
-------------------

Open XDMoD 9.0.0 is a major release that includes new features along with many
enhancements and bug fixes.

You may upgrade directly from 8.5.0 or 8.5.1.

### GPU Metrics

This is the first version of Open XDMoD that supports GPU metrics in the jobs
realm.  During the upgrade process there is a prompt where the option is given
to re-ingest and re-aggregate job records in the database that may contain GPU
data for the Slurm and PBS resource managers.

Since Open XDMoD 6.5 data from the Slurm `ReqGRES` field has been stored and
will be used as the source of GPU data during the upgrade process.  Note that
newly ingested data will use the `AllocTRES` field as the primary source of GPU
data for Slurm job records, but that field is not available for previously
ingested data.

Since Open XDMoD 3.5 data from the PBS `Resource_List.nodes` field has been
stored and will be used as the source of GPU data during the upgrade process.

See the [GPU Metrics](gpu-metrics.html) documentation for more details.

### Slurm Input File Format Changes

The input file format for Slurm data has changed to include the `AllocTRES`
field.

The slurm shredder has also been updated to accept jobs in all states and to
ignore jobs that have not ended.  Due to this change the `--state` option of
the `sacct` command is no longer recommended.  If an unrecognized state is
encountered a warning will be generated.

**If you are generating Slurm input for the `xdmod-shredder` command then you
will need to make the appropriate changes.**  Refer to the [Slurm
Notes](resource-manager-slurm.html#input-format) for the example `sacct`
command.  If you are using the `xdmod-slurm-helper` command then no changes are
necessary.

### Configuration File Changes

The `xdmod-upgrade` script will migrate user editable configuration files to
the new version, excluding the Apache and `logrotate` configuration files,
which may need to be manually updated.

The Apache access log and error log now use the [piped logging
feature][apache-logs-piped] combined with the [Apache `rotatelogs`
program][apache-rotatelogs] to rotate logs based on the size of the log files
to limit the total amount of storage used by these log files.  Since these
files are no longer rotated by `logrotate`, any preexisting rotated log files
will need to be manually deleted.

If the `logrotate` configuration file was modified you will need to manually
remove the section for the Apache log files if you use the piped logging
feature with `rotatelogs`.

### Database Changes

The `xdmod-upgrade` script will migrate the database schemas to the new
version.  Tables may be altered the first time they are used during ingestion.

- The `moddb`.`ReportTemplateACL` database table is no longer used and is
removed by the upgrade script.
- The following tables are altered to store GPU data:
  `mod_shredder`.`shredded_job_slurm`, `mod_shredder`.`shredded_job`,
  `mod_shredder`.`staging_job`, `mod_hpcdb`.`hpcdb_jobs`, `modw`.`job_tasks`,
  and tables in `modw_aggregates` prefixed with `jobfact_by_`.
- New table `moddb`.`gpu_buckets` for GPU count ranges used for "Group By GPU
  Count".
- Added another index to `mod_logger`.`log_table` to improve performance of
  queries used by the administrative dashboard's "Log Data" tab.  If this table
  contains tens of millions of rows it may take over an hour to add the index.
  It may be desirable to delete old log data from this table before performing
  the migration if the data is no longer needed.

- The `modw_cloud`.`account`, `modw_cloud`.`instance_type` and `modw_cloud`.`instance`
tables have had their Primary Keys changed to better support the local and global filters
in the Metric Explorer.

- Because of database changes to `modw_cloud`.`account`, `modw_cloud`.`instance_type`
tables any saved charts or reports using the Account or Configuration group by in the
Cloud realm should be recreated.

[github-latest-release]: https://github.com/ubccr/xdmod/releases/latest
[mysql-config]: software-requirements.md#mysql
[apache-logs-piped]: https://httpd.apache.org/docs/2.4/logs.html#piped
[apache-rotatelogs]: https://httpd.apache.org/docs/2.4/programs/rotatelogs.html
