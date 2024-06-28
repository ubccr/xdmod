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

# !!! XDMoD 11.0 Upgrade Process Changes !!!
Due to our ongoing modernization efforts XDMoD 11.0 will require PHP 7.4. To accommodate this change there are a few
additional steps required that are outside the typical upgrade process. Below we have included the upgrade steps if you
are upgrading from both CentOS 7 and Rocky 8. If you run into any problems during your upgrade process, please submit a
ticket to `ccr-xdmod-help@buffalo.edu` and we will do our best to help.


### Server: EL7, XDMoD: 10.5, PHP: 5.4
If you are still using CentOS 7 and are wanting to upgrade to XDMoD 11.0 on Rocky or Alma 8, please follow the steps below.
At the end of this process you should expect to have a working XDMoD 10.5.0 installation on a Rocky 8 server that
contains all of your current data. After which you can then follow the upgrade procedure that immediately follows this
section which starts at `Server: EL8, XDMoD: 10.5, PHP: 7.2`.

1. Install a fresh copy of XDMoD 10.5 on a new Rocky 8 server [https://open.xdmod.org/10.5/install-rpm.html](https://open.xdmod.org/10.5/install-rpm.html)
   1. Instead of running `xdmod-setup` do steps 2 & 3 below.
2. Copy the contents of `/etc/xdmod` (or if you have a source install the contents of `/path/to/your/xdmod/etc/`) from the CentOS 7
   server to the Rocky 8 server.
    1. <span style="color: orange;">***NOTE:***</span>If the database host has changed then on the Rocky 8 Server,
       update the `host = ` entries in `/etc/xdmod/portal_settings.ini` to reflect this.
3. Export the database from the CentOS 7 installation and transfer the files to the Rocky 8 server.
    1. For example, `mysqldump --databases mod_hpcdb mod_logger moddb modw modw_aggregates modw_cloud modw_filters > backup.sql`
       1. To make the process of importing the data less error-prone, please update the following sed snippet with your installations MySQL user (`` `user`@`host` ``) and run it against the dumped sql file(s).
          ``sed -i 's|DEFINER=`xdmod`@`localhost`||g' backup.sql``
4. Import the CentOS 7 exported database files into the Rocky 8 server's database.
    1. `mysql < backup.sql`
5. **NOTE:** MariaDB / MySQL users are defined as `'username'@'hostname'` so if the hostname of the new Rocky 8 web server is different than the hostname of the old CentOS 7 web server, you will need to make sure that this change is reflected in the database.
    1. Run the following from an account that has db admin privileges to ensure the XDMoD user is correct: `mysql -e "UPDATE mysql.user SET Host = '<insert new XDMoD web server hostname here>' WHERE username = 'xdmod';"`    
6. Restart the web server / database on the Rocky 8 server and confirm that everything is working as expected.
7. Next, follow the upgrade process detailed below on the Rocky 8 Server.

### Server: EL8, XDMoD: 10.5, PHP: 7.4
If you have XDMoD 10.5 installed on Rocky 8 then please follow the steps below:

Update the PHP module to 7.4
```shell
$ dnf module -y reset php
$ dnf module -y enable php:7.4
```

Install PHP 7.4 and some require pre-reqs for PHP Pear packages
```shell
$ dnf install -y php libzip-devel php-pear php-devel
```

Some Notes:
- If you run the above command and dnf tells you that the packages are already installed, double-check the contents of
  `/etc/dnf/dnf.conf` if `best=False` is present then change it to `best=True`. Re-run the command above, and it should
  now find / install the 7.4 version of the packages.
- You may also see some `PHP: Warning` messages during this process, specifically:
```
PHP Warning:  PHP Startup: Unable to load dynamic library 'mongodb.so' (tried: /usr/lib64/php/modules/mongodb.so (/usr/lib64/php/modules/mongodb.so: undefined symbol: _zval_ptr_dtor), /usr/lib64/php/modules/mongodb.so.so (/usr/lib64/php/modules/mongodb.so.so: cannot open shared object file: No such file or directory)) in Unknown on line 0
```
*Not to worry, this will be resolved by the next step*

Install the mongodb PHP Pear package
```shell
$ yes '' | pecl install mongodb
```

You may now continue with the standard upgrade steps below.

### Download Latest Open XDMoD RPM package

Download available at [GitHub][github-latest-release].

### Install the RPM

    # dnf install xdmod-{{ page.sw_version }}-1.0.el8.noarch.rpm

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

Additional 11.0.0 Upgrade Notes
-------------------

Open XDMoD 11.0.0 is a major release that includes new features along with many
enhancements and bug fixes.

Open XDMoD is now no longer bundled with any non-commercial licenses. The charting library used in Open XDMoD has changed from [Highcharts](https://www.highcharts.com/) to [Plotly JS](https://plotly.com/javascript/), an open source library. This transition removes the non-commercial license required from the Highcharts library. Please refer to the [license notices](notices.md) for more information about the open source licenses bundled with Open XDMoD. For more information please refer to [release notes](https://github.com/ubccr/xdmod/releases) for Open XDMoD 11.0.

### Configuration File Changes

The `xdmod-upgrade` script will add settings to `portal_settings.ini` to support the new [Data Analytics Framework](data-analytics-framework.md):
* A new section `[api_token]` will be added with `expiration_interval = "6 months"`.
* `rest_raw_row_limit = "10000"` will be added to the `[warehouse]` section.

### Database Changes

The `xdmod-upgrade` script will create the new `moddb.user_tokens` table to support API tokens for the new [Data Analytics Framework](data-analytics-framework.md).

[github-latest-release]: https://github.com/ubccr/xdmod/releases/latest
[mysql-config]: configuration.md#mysql-configuration
