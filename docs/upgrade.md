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

    # yum install xdmod-8.1.0-1.0.el7.noarch.rpm

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
`/opt/xdmod-8.0.0` and the new version of Open XDMoD will be installed at
`/opt/xdmod-8.1.0`.  It is recommended to install the new version of Open XDMoD
in a different directory than your existing version.

### Download Latest Open XDMoD Source Package

Download available at [GitHub][github-latest-release].

### Extract and Install Source Package

    $ tar zxvf xdmod-8.1.0.tar.gz
    $ cd xdmod-8.1.0
    # ./install --prefix=/opt/xdmod-8.1.0

Likewise, install the latest `xdmod-appkernels` or `xdmod-supremm`
tarballs if you have those installed.

### Copy Current Config Files

    # cp /opt/xdmod-8.0.0/etc/portal_settings.ini /opt/xdmod-8.1.0/etc
    # cp /opt/xdmod-8.0.0/etc/hierarchy.json      /opt/xdmod-8.1.0/etc
    # cp /opt/xdmod-8.0.0/etc/organization.json   /opt/xdmod-8.1.0/etc
    # cp /opt/xdmod-8.0.0/etc/resource_specs.json /opt/xdmod-8.1.0/etc
    # cp /opt/xdmod-8.0.0/etc/resources.json      /opt/xdmod-8.1.0/etc
    # cp /opt/xdmod-8.0.0/etc/update_check.json   /opt/xdmod-8.1.0/etc

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

    # /opt/xdmod-8.1.0/bin/xdmod-upgrade

8.0.0 to 8.1.0 Upgrade Notes
----------------------------

### Configuration File Changes

- Changes `datawarehouse.json`:
    - Adds Cloud realm `group_bys`.
- Changes `portal_settings.ini`:
    - Removes `singlejobviewer` option.
    - Adds an option to show or hide the local login modal dialog for single
      sign on configurations.
    - Added subject prefix option for outbound emails.
- Removes `processor_buckets.json`:
    - Use `etl/etl_data.d/cloud_common/processor_buckets.json` to change
      processor bucket ranges.
- Changes `roles.d/cloud.json`:
    - If the cloud realm is enabled this file will be updated with permissions
      for the new `group_by`s.
- Changes files in `etl/`:
    - Various additions, improvements and bug fixes.

### Database Changes

- Drops existing tables in `modw_cloud` if cloud realm is enabled.
- Updates user profile data in `moddb` to normalize Metric Explorer
  configuration of saved charts.

[github-latest-release]: https://github.com/ubccr/xdmod/releases/latest
[mysql-config]: software-requirements.md#mysql
