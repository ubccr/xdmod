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

After upgrading the RPM, you may need to manually update your Apache
config file (`/etc/httpd/conf.d/xdmod.conf`).  Check to see if a file
named `/etc/httpd/conf.d/xdmod.conf.rpmnew` exists.  If so, you'll need
to merge the changes into `/etc/httpd/conf.d/xdmod.conf`.

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

8.5.0 Upgrade Notes
-------------------

Open XDMoD 8.5.0 is a major release that includes new features along with many
enhancements and bug fixes.

You may upgrade directly from 8.1.0, 8.1.1 or 8.1.2 to 8.5.0.

### Configuration File Changes

The `xdmod-upgrade` script will migrate user editable configuration files to
the new version.

- Changes `resources.json`:
    - No longer uses `resource_type_id` and now uses `resource_type` which
      references the resource type key name from `resource_types.json`.
- Changes `resource_types.json`:
    - This file now uses a new format.
- Changes `portal_settings.ini`:
    - Adds `user_dashboard` option.
    - Adds data warehouse batch export configuration options.
- Changes `datawarehouse.json` and files in `datawarehouse.d/`:
    - Reorganizes data warehouse configuration.
- Changes `roles.json` and files in `roles.d`:
    - Reorganizes role configuration.
    - Adds permissions for data warehouse batch export
- Changes files in `etl/`:
    - Various additions, improvements and bug fixes.
- Changes `cron` configuration:
    - Adds cron job for data warehouse batch export.

### Database Changes

The `xdmod-upgrade` script will migrate the database schemas to the new
version.  Tables may be altered the first time they are used during ingestion.

- Adds `mod_hpcdb`.`resource_type_realms` table.
- Changes `mod_hpcdb`.`hpcdb_resource_types` primary key.
- Changes `mod_hpcdb`.`hpcdb_resources` foreign key constraint.
- Alters `mod_shredder`.`staging_resource_config` table.
- Adds `mod_shredder`.`staging_resource_type_realms` table.
- Increases `modw_cloud`.`openstack_raw_event`.`user_name` column length.
- Increases `mod_hpcdb`.`hpcdb_system_accounts`.`username` column length.
- Increases `modw`.`systemaccount`.`username` column length.
- Increases `mod_shredder`.`staging_storage_usage`.`user_name` column length.
- Increases `mod_shredder`.`staging_storage_usage`.`pi_name` column length.
- Adds `modw`.`jobfact_by_day_joblist` table.
- Adds `modw`.`batch_export_requests` table.
- Adds `moddb`.`ReportTemplateChartsStaging` table.
- Adds `moddb`.`ReportTemplatesStaging` table.

8.5.1 Upgrade Notes
-------------------

Open XDMoD 8.5.1 is a bug fix release that fixes an issue with detecting
enabled realms when only one resource is configured.

You may upgrade directly from 8.1.0, 8.1.1, 8.1.2 or 8.5.0 to 8.5.1.

[github-latest-release]: https://github.com/ubccr/xdmod/releases/latest
[mysql-config]: software-requirements.md#mysql
