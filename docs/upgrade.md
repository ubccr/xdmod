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

### Install RPM package(s)

Note that if you have installed any of the optional modules for Open XDMoD, you
should also include their new RPM file(s) on the same `dnf install` command
line below that you use to install the new Open XDMoD RPM file. The upgrade
guides for each of the optional modules are linked below; these each contain a
link to the GitHub page for the module release, which has the link to their
RPM file.

- [Application Kernels](https://appkernels.xdmod.org/{{ page.version }}/ak-upgrade.html)
- [Job Performance (SUPReMM)](https://supremm.xdmod.org/{{ page.version }}/supremm-upgrade.html)
- [OnDemand](https://ondemand.xdmod.org/{{ page.version }}/upgrade.html)

If your web server can reach GitHub via HTTPS, you can install the RPM
package(s) directly:

    # dnf install https://github.com/ubccr/xdmod/releases/download/v{{ page.rpm_version }}/xdmod-{{ page.rpm_version }}.el8.noarch.rpm [optional module RPMs]

Otherwise, you can download the RPM file from the [GitHub page for the
release][github-release] and install it (along with any of the optional modules
you have installed as explained above):

    # dnf install xdmod-{{ page.rpm_version }}.el8.noarch.rpm [optional module RPMs]

After installing the RPM(s), you may need to manually merge changes to any
files that you had previously manually changed in your Open XDMoD installation.
Any such files will have extensions of `.rpmnew` or `.rpmsave` and can be
located with the following command. The exception to this is
`portal_settings.ini`; this file will be updated by the `xdmod-upgrade` command
later; any manual changes you want to merge to this file should be merged after
running the `xdmod-upgrade` command in a later step below.

    # find /etc/xdmod /usr/bin /usr/lib64/xdmod /usr/share/xdmod -regextype sed -regex '.*\.rpm\(new\|save\)$'

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
extract, and install their source packages, too. The upgrade guides for
each of the optional modules are linked below; these each contain a link to
the GitHub page for the module release, which has the link to their source
package.

- [Application Kernels](https://appkernels.xdmod.org/{{ page.version }}/ak-upgrade.html)
- [Job Performance (SUPReMM)](https://supremm.xdmod.org/{{ page.version }}/supremm-upgrade.html)
- [OnDemand](https://ondemand.xdmod.org/{{ page.version }}/upgrade.html)

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

Additional 11.5.0 Upgrade Notes
-------------------

[github-release]: https://github.com/ubccr/xdmod/releases/tag/v{{ page.rpm_version }}
[mysql-config]: configuration.html#mariadb-configuration
