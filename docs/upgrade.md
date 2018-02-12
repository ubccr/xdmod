---
title: Upgrade Guide
---

General Upgrade Notes
---------------------

- Open XDMoD only supports upgrading to a new version from the version
  that directly precedes it.  If you need to upgrade from an older
  version you must upgrade through all the intermediate versions or
  perform a clean installation.
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

After upgrading the RPM, you may need to manually update your Apache
config file (`/etc/httpd/conf.d/xdmod.conf`).  Check to see if a file
named `/etc/httpd/conf.d/xdmod.conf.rpmnew` exists.  If so, you'll need
to merge the changes into `/etc/httpd/conf.d/xdmod.conf`.

### Download Latest Open XDMoD RPM package

Download available at [GitHub][github-latest-release].

### Install the RPM

    # yum install xdmod-x.y.z-1.0.el6.noarch.rpm

Likewise, install the latest `xdmod-appkernels` or `xdmod-supremm` RPM
files if you have those installed.

After upgrading the package you may need to manually merge any files
that you have manually changed before the upgrade.  You do not need to
merge `portal_settings.ini`.  This file will be updated by the upgrade
script.  If you have manually edited this file, you should create a
backup and merge any changes after running the upgrade script.

### Upgrade Database Schema and Config Files

    # xdmod-upgrade

Source Package Upgrade Process
------------------------------

This example assumes that your previous version of Open XDMoD is
installed at `/opt/xdmod-old` and the new version of Open XDMoD will be
installed at `/opt/xdmod-new`.  It is recommended to install the new
version of Open XDMoD in a different directory than your existing
version.

### Download Latest Open XDMoD Source Package

Download available at [GitHub][github-latest-release].

### Extract and Install Source Package

    $ tar zxvf xdmod-x.y.z.tar.gz
    $ cd xdmod-x.y.z
    # ./install --prefix=/opt/xdmod-new

Likewise, install the latest `xdmod-appkernels` or `xdmod-supremm`
tarballs if you have those installed.

### Copy Current Config Files

    # cp /opt/xdmod-old/etc/portal_settings.ini /opt/xdmod-new/etc
    # cp /opt/xdmod-old/etc/hierarchy.json      /opt/xdmod-new/etc
    # cp /opt/xdmod-old/etc/organization.json   /opt/xdmod-new/etc
    # cp /opt/xdmod-old/etc/resource_specs.json /opt/xdmod-new/etc
    # cp /opt/xdmod-old/etc/resources.json      /opt/xdmod-new/etc
    # cp /opt/xdmod-old/etc/update_check.json   /opt/xdmod-new/etc

If you have manually changed (i.e. not using `xdmod-setup`) any of the
other config files you may need to merge your changes into the new
config files.  You should `diff` the config files to see what has
changed in the new version.  You do not need to merge
`portal_settings.ini`.  This file will be updated by the upgrade script.
If you have manually edited this file, you should create a backup and
merge any changes after running the upgrade script.

### Upgrade Database Schema and Config Files

    # /opt/xdmod-new/bin/xdmod-upgrade

7.0.1 to 7.1.0 Upgrade Notes
----------------------------

- This upgrade includes RPM packaging changes.
    - The RPM will create an `xdmod` user and group if they do not already
      exist.
    - The RPM will change the permissions of `/var/log/xdmod/query.log` and
      `/var/log/xdmod/exceptions.log` so that they are writable by the `apache`
      user and the `xdmod` group.
    - The RPM will change the ownership and permissions of `/var/log/xdmod`
      so that it is writable by the `apache` user
      and the `xdmod` group.

7.0.0 to 7.0.1 Upgrade Notes
----------------------------

- This upgrade does not in include any database schema changes.
- This upgrade does not include any config file format changes, but the
  upgrade script will recreate `portal_settings.ini` with the new
  version number.

6.6.0 to 7.0.0 Upgrade Notes
----------------------------

- This upgrade includes database schema changes.
    - Modifies `moddb` schema to remove unused tables and add new ACL tables.

6.5.0 to 6.6.0 Upgrade Notes
----------------------------

- This upgrade includes database schema changes.
    - Modifies `moddb` schema.
    - Modifies `modw` schema.

6.0.0 to 6.5.0 Upgrade Notes
----------------------------

- This upgrade includes database schema changes.
    - Modifies `mod_shredder` schema.
    - Modifies `mod_hpcdb` schema.
- This upgrade includes Slurm shredder changes.
    - If you are using `xdmod-shredder` with Slurm (and not
      `xdmod-slurm-helper`) you must update the argument to the `sacct`
      `--format` option.  See the
      [Slurm resource manager notes](resource-manager-slurm.html) for details.

5.6.0 to 6.0.0 Upgrade Notes
----------------------------

**Important Note**: Highcharts has been updated from v3.0.9 to v4.2.5. If you
are a commercial user as defined by the Highcharts license terms and your
Highcharts license does not cover the new version, you will need to acquire a
new license.

- This upgrade includes database schema changes.
    - Modifies `mod_shredder` schema.
    - Modifies `moddb` schema.
    - Modifies `modw` schema.
    - The database tables for both LSF and Slurm are altered.  If you use either
      of these resource managers the database migration may take over an hour if
      you have millions of job records in your database.
    - Updates SUPReMM user search history to allow editing of searches created
      in previous versions.
- This upgrade includes config file format changes.
    - Upgrades `roles.json` and `roles.d/*.json` files to new format.
    - Adds new option to `portal_settings.ini`.
        - The `maintainer_email_signature` options in the `general` section can
          be set to specify the email signature used in emails sent from Open
          XDMoD.
    - Adds new option to `portal_settings.d/supremm.ini` (if the `xdmod-supremm`
      module is installed.
        - The `schema_file` option in the `supremm-general` section can be set
          to specify the ETL schema file.

5.5.0 to 5.6.0 Upgrade Notes
----------------------------

- This upgrade includes database schema changes.
    - This version adds a new schema, `modw_filters`. Creating this schema
      requires admin credentials for your MySQL server.
    - Modifies `mod_logger` schema.
    - Modifies `mod_shredder` schema.
    - Modifies `moddb` schema.
    - Modifies `modw` schema.
- This upgrade includes config file format changes.
    - If you have created custom roles that are associated with data
      dimensions, you will need to add the property `dimensions` to their
      definitions in `roles.json` and/or `roles.d`. The value of this property
      should be an array of the IDs of the dimensions used.
        - For example, if you created a role based around queues, you
          would add `dimensions: ["queue"]` to the definition.
    - Adds additional options to `portal_settings.ini`.

**NOTE**: Following this upgrade, filter lists and other components that use
dimension values **will not work** until data aggregation runs again. To get
these components working again without performing aggregation, you can run
`xdmod-ingestor --build-filter-lists`.

5.0.0 to 5.5.0 Upgrade Notes
----------------------------

- This upgrade includes database schema changes.
- This upgrade includes config file format changes.

**NOTE**: The database migration in this upgrade is substantial and may
take over an hour if you have millions of job records in your database.

4.5.2 to 5.0.0 Upgrade Notes
----------------------------

- This upgrade includes database schema changes.
- This upgrade includes config file format changes.

4.5.1 to 4.5.2 Upgrade Notes
----------------------------

- This upgrade does not in include any database schema changes.
- This upgrade does not include any config file format changes, but the
  upgrade script will recreate `portal_settings.ini` with the new
  version number.

4.5.0 to 4.5.1 Upgrade Notes
----------------------------

- This upgrade does not in include any database schema changes.
- This upgrade does not include any config file format changes, but the
  upgrade script will recreate `portal_settings.ini` with the new
  version number.

3.5.0 to 4.5.0 Upgrade Notes
----------------------------

- This upgrade includes database schema changes.
- This upgrade includes config file format changes.

[github-latest-release]: https://github.com/ubccr/xdmod/releases/latest
