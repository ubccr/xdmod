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
  `xdmod-appkernels`, `xdmod-supremm`, or `xdmod-ondemand`), upgrade those to
  the latest version before running `xdmod-upgrade`.

RPM Upgrade Process
-------------------

### Download Latest Open XDMoD RPM package

Download available at [GitHub][github-latest-release].

### Install the RPM

    # yum install xdmod-{{ page.sw_version }}-1.0.el7.noarch.rpm

Likewise, install the latest `xdmod-appkernels`, `xdmod-supremm`, and/or
`xdmod-ondemand` RPM files if you have those modules installed.

After upgrading the package you may need to manually merge any files
that you have manually changed before the upgrade.  You do not need to
merge `portal_settings.ini`.  This file will be updated by the upgrade
script.  If you have manually edited this file, you should create a
backup and merge any changes after running the upgrade script.

### Verify Server Configuration Settings

Double check that the MySQL server configuration settings are consistent with
the recommended values listed in the [Configuration Guide][mysql-config].

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

    # tar zxvf xdmod-{{ page.sw_version }}.tar.gz
    # cd xdmod-{{ page.sw_version }}
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
the recommended values listed in the [Configuration Guide][mysql-config].

### Upgrade Database Schema and Config Files

    # /opt/xdmod-{{ page.sw_version }}/bin/xdmod-upgrade

10.5.0 Upgrade Notes
-------------------

Open XDMoD 10.5.0 is a major release that includes new features along with many
enhancements and bug fixes.

You may upgrade directly from 10.0.X.

Included in these new features is official support for the Rocky 8 operating system. This will allow organizations
to migrate their XDMoD installations from the soon-to-be end-of-life CentOS 7 to a currently supported OS. The officially
recommended process of migrating from a CentOS 7 XDMoD 10.0.X installation to an Rocky 8 XDMoD 10.5 installation is as follows:
1. Update the CentOS 7 XDMoD 10.0.X installation to XDMoD 10.5
2. Install a fresh copy of XDMoD 10.5 on a new Rocky 8 server.
3. Copy the contents of `/etc/xdmod` from the CentOS 7 server to the Rocky 8 server.
    1. Adjust database connection properties as appropriate.
4. Export the database from the CentOS 7 installation and transfer the files to the Rocky 8 server.
    1. For example, using `mysqldump`.
5. Import the CentOS 7 exported database files into the Rocky 8 server's database.
6. Ensure that you have added the `sql_mode=` line to the `[server]` section of `/etc/my.cnf.d/mariadb-server.cnf` on the Rocky 8 server.
7. Restart the web server / database on the Rocky 8 server and confirm that everything is working as expected.

Also included in the new features is the new [Data Analytics Framework](data-analytics-framework.md) that allows users to programmatically obtain data from the data warehouse via a Python API.

### Configuration File Changes

The `xdmod-upgrade` script will add settings to `portal_settings.ini` to support the new [Data Analytics Framework](data-analytics-framework.md):
* A new section `[api_token]` will be added with `expiration_interval = "6 months"`.
* `rest_raw_row_limit = "10000"` will be added to the `[warehouse]` section.

### Database Changes

The `xdmod-upgrade` script will create the new `moddb.user_tokens` table to support API tokens for the new [Data Analytics Framework](data-analytics-framework.md).

[github-latest-release]: https://github.com/ubccr/xdmod/releases/latest
[mysql-config]: configuration.md#mysql-configuration
