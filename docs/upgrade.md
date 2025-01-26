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

### Run the XDMoD ingestor

    # xdmod-ingestor

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

### Run the XDMoD ingestor

    # /opt/xdmod-{{ page.sw_version }}/bin/xdmod-ingestor

Additional 11.5.0 Upgrade Notes
-------------------

Open XDMoD 11.5 updates the database tables to use UTF8 character encoding.
Prior versions of Open XDMoD did not specify the character encoding and used the database
software default values for the database tables (which was latin1
for MariaDB 10). The ingestor and shredder pipelines used different character encoding
(some used UTF8 encoding, some the database connection defaults).  This meant that non
ASCII characters could be incorrectly displayed in XDMoD.

This upgrade changes Open XDMoD database tables to support UTF8 character encoding and
updates the shredder and ingestor to process UTF8 encoded files. This ensures
that information in new logs loaded into Open XDMoD will display correctly. The upgrade
does not automatically fix the display of previously loaded non-ASCII data. The table
below shows the steps that can be taken for each data type.

| Field  |    Remedy |
| -----  | --------- |
| User / PI names | Ensure the information in names.csv is encoded in UTF8 character set. Run the `xdmod-import-csv` and `xdmod-ingestor` as described in the [Names Guide](user-names.md) |
| Hierarchy | Ensure the information in hierarchy.csv is encoded in UTF8 character set. Run the `xdmod-import-csv` and `xdmod-ingestor` as described in the [Hierarchy Guide](hierarchy.md) |
| Job name in the single job viewer | The job name string for jobs that have already been shredded and ingested into XDMoD will not be corrected automatically. It is necessary to truncate the data for all jobs and reingest following the instruction in the [FAQ](faq.md#how-do-i-delete-all-my-job-data-from-open-xdmod). |



[github-latest-release]: https://github.com/ubccr/xdmod/releases/latest
[mysql-config]: configuration.md#mysql-configuration
