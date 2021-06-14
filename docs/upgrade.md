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

*NOTE: This upgrade removes the `PhantomJS` and `ghostscript` dependencies; it adds dependencies of `chromium`, `libRSVG` and `exiftool`. These new dependencies will need to be manually installed. See the [Software Requirements](software-requirements.html) for more details.
`PhantomJS` is no longer required, however, it WILL NOT be removed automatically; it will need to be removed manually.*

### Verify Server Configuration Settings

Double check that the MySQL server configuration settings are consistent with
the recommended values listed in the [Configuration Guide][mysql-config].

### Upgrade Database Schema and Config Files

    # /opt/xdmod-{{ page.sw_version }}/bin/xdmod-upgrade

9.5.0 Upgrade Notes
-------------------

Open XDMoD 9.5.0 is a major release that includes new features along with many
enhancements and bug fixes.

You may upgrade directly from 9.0.0.

*NOTE: This upgrade removes the `PhantomJS` and `ghostscript` dependencies; it adds dependencies of `chromium`, `libRSVG` and `exiftool`.. These new dependencies will be automatically installed by the RPM.
`PhantomJS` is no longer required, however, it WILL NOT be removed automatically; it will need to be removed manually.*

### Configuration File Changes

The `xdmod-upgrade` script will migrate user editable configuration files to the new version and ask for the location of `chromium`.

### Slurm Input File Format Changes

The input file format for Slurm data has changed to remove the `ReqGRES` field.

**If you are generating Slurm input for the `xdmod-shredder` command then you
will need to make the appropriate changes.**  Refer to the [Slurm
Notes](resource-manager-slurm.html#input-format) for the example `sacct`
command.  If you are using the `xdmod-slurm-helper` command then no changes are
necessary.

### Database Changes

[github-latest-release]: https://github.com/ubccr/xdmod/releases/latest
[mysql-config]: configuration.md#mysql-configuration

### Cloud Realm Changes

This upgrade fixed a bug with the memory buckets for the cloud realm which was causing certain cloud data to not display correctly. To ensure that all previous cloud data is being displayed and recorded correctly, you can re-ingest the cloud data after the upgrade is complete by using the [`xdmod-shredder`](shredder.md) and [`xdmod-ingestor`](ingestor.md) commands.

    $ xdmod-shredder -r RESOURCE_NAME -d /path/to/logs
    $ xdmod-ingestor --datatype=CLOUD_DATATYPE
    $ xdmod-ingestor --aggregate=cloud --last-modified-start-date "2017-01-01 00:00:00"

The `CLOUD_DATATYPE` should be either `openstack` or `genericcloud` and `RESOURCE_NAME` should be the name of the cloud resource you and shredding data for. See documentation for the [`xdmod-shredder`](shredder.md) and [`xdmod-ingestor`](ingestor.md) commands for more information.
