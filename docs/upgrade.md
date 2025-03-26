---
title: Upgrade Guide
---

General Upgrade Notes
---------------------

- Open XDMoD version numbers are of the form X.Y.Z, where X.Y is the major
  version number and Z is the minor version number.
    - Software changes for minor versions include security updates and bug
      fixes.
    - Software changes for major versions usually have new features added,
      database structure changes, and non-backwards compatible changes.
    - Major version numbers usually (but not always) increment by 0.5, e.g.,
      9.0, 9.5, 10.0, 10.5, etc.
- Unless otherwise noted below, Open XDMoD only supports upgrades to:
    - Minor versions of the same major version (e.g., from 9.5.0 to 9.5.1,
      from 10.0.0 to 10.0.3, etc.),
    - The next major version (e.g., from 9.5.0 to 10.0.0, from 10.0.2 to
      11.0.0, etc.), or
    - Minor versions of the next major version (e.g., from 9.5.0 to 10.0.1,
      from 10.5.1 to 11.0.1, etc.).
- If you need to jump more than one major version, you must incrementally
  upgrade to each of the intermediate major versions (or a minor version
  thereof), e.g., if you want to upgrade from 9.5.1 to 11.0.2, then you must
  upgrade from 9.5.1 to 10.0.\*, then from 10.0.\* to 10.5.\*, then from
  10.5.\* to 11.0.2.
- Make backups of your Open XDMoD configuration files and databases before
  running the upgrade script. The upgrade script may overwrite your current
  configuration files and data.
- Do not change the version in `portal_settings.ini` before running the
  upgrade script. The version number will be changed by the upgrade
  script.
- Make sure to follow the instructions below in the proper order, and note that
  there may be version-specific upgrade notes. If you have installed any of the
  optional modules for Open XDMoD, they may have their own version-specific
  upgrade notes as well, see:
    - [Application Kernels](https://appkernels.xdmod.org/{{ page.version }}/ak-upgrade.html)
    - [Job Performance (SUPReMM)](https://supremm.xdmod.org/{{ page.version }}/supremm-upgrade.html)
    - [OnDemand](https://ondemand.xdmod.org/{{ page.version }}/upgrade.html)

RPM Upgrade Process
-------------------

### Download Open XDMoD RPM package

Download available at [GitHub][github-release].

### Install the RPM

    # dnf install xdmod-{{ page.rpm_version }}.el8.noarch.rpm

If you have installed any of the optional modules for Open XDMoD, download and
install their RPMs, too:
- [Application Kernels](https://appkernels.xdmod.org/{{ page.version }}/ak-install-rpm.html)
- [Job Performance (SUPReMM)](https://supremm.xdmod.org/{{ page.version }}/supremm-install.html)
- [OnDemand](https://ondemand.xdmod.org/{{ page.version }}/install.html)

After upgrading the package you may need to manually merge any files
that you have manually changed before the upgrade.  You do not need to
merge `portal_settings.ini`. This file will be updated by the upgrade
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

### Download Open XDMoD Source Package

Download available at [GitHub][github-release]. Make sure to download
`xdmod-{{ page.sw_version }}.tar.gz`, not the GitHub-generated "Source code"
files.

### Extract and Install Source Package

    # tar zxvf xdmod-{{ page.sw_version }}.tar.gz
    # cd xdmod-{{ page.sw_version }}
    # ./install --prefix=/opt/xdmod-{{ page.sw_version }}

If you have installed any of the optional modules for Open XDMoD, download,
extract, and install their source packages, too:
- [Application Kernels](https://appkernels.xdmod.org/{{ page.version }}/ak-install-source.html)
- [Job Performance (SUPReMM)](https://supremm.xdmod.org/{{ page.version }}/supremm-install.html)
- [OnDemand](https://ondemand.xdmod.org/{{ page.version }}/install.html)

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

### Update Apache Configuration

Make sure to update `/etc/httpd/conf.d/xdmod.conf` to change
`/opt/xdmod-{{ page.prev_sw_version }}` to `/opt/xdmod-{{ page.sw_version }}`.

Additional 10.5.0 Upgrade Notes
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

[github-release]: https://github.com/ubccr/xdmod/releases/tag/v{{ page.rpm_version }}
[mysql-config]: configuration.html#mariadb-configuration
