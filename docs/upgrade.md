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

XDMoD 11.0 no longer supports the obsolete Centos 7 OS. XDMoD 11.0 is supported on
Rocky 8 with the PHP version 7.4 that [is supported until May 2029](https://access.redhat.com/support/policy/updates/rhel-app-streams-life-cycle#rhel8_full_life_application_streams).

We support the following upgrade paths:
- XDMoD 10.5 on Centos 7 to XDMoD 11.0 on Rocky 8
- XDMoD 10.5 on Rocky 8, PHP 7.2 to XDMoD 11.0 on Rocky 8, PHP 7.4

If you run into any problems during your upgrade process, please submit a
ticket to `ccr-xdmod-help@buffalo.edu` and we will do our best to help.


### Server: EL7, XDMoD: 10.5, PHP: 5.4, MySQL or MariaDB 5.5
If you are using CentOS 7 and will upgrade to XDMoD 11.0 on Rocky or Alma 8, please follow the steps below.

At the end of this process you should expect to have a working XDMoD 10.5 installation on a Rocky 8 server that
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

### Server: EL8, XDMoD: 10.5, PHP: 7.2, MariaDB 10.3

If you have XDMoD 10.5 installed on Rocky 8 then please follow the steps below:

Update the PHP module to 7.4
```shell
$ dnf module -y reset php
$ dnf module -y enable php:7.4
```

Install PHP 7.4 and some require pre-reqs for PHP Pear packages
```shell
$ dnf install -y php make libzip-devel php-pear php-devel
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
$ yes '' | pecl install mongodb-1.18.1
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

Open XDMoD is now no longer bundled with libraries that have license
restrictions for commercial or government use. The charting library used in
Open XDMoD has changed from [Highcharts](https://www.highcharts.com/) to
[Plotly JS](https://plotly.com/javascript/), an open source library.
Please refer to the [license notices](notices.md) for more information about the open source licenses bundled with Open XDMoD.
 For more information please refer to [release notes](https://github.com/ubccr/xdmod/releases) for Open XDMoD 11.0 or under
the "Release Notes" in the "About" tab in the XDMoD portal.

After the upgrade, users wishing to use the
[`xdmod-data`](https://github.com/ubccr/xdmod-data) Python package to retrieve
data from your Open XDMoD installation will need `xdmod-data` version
1.0.1 or greater. To upgrade the Python package to the latest version, users
can run `pip install --upgrade xdmod-data`.

### Configuration File Changes

#### `portal_settings.ini`

For the [Data Analytics Framework](data-analytics-framework.md), the REST
endpoint for retrieving raw data will now stream all the data as a JSON text
sequence rather than returning a single JSON object that had a certain limited
number of rows (default 10,000) configured by the `rest_raw_row_limit` setting.
This setting is no longer needed, so it will be removed during the upgrade.

The user manual is now built using the Python Sphinx library, which makes the
`user_manual` setting no longer needed, so it will be removed during the
upgrade.

#### `resources.json` and `resource_specs.json`

New fields have been added to the `resources.json` and `resource_specs.json` files to support the new `Resource Specifications` realm.

The `resources.json` file includes a new field `resource_allocation_type`. The `resource_allocation_type` field indicates how this resource is allocated to users, such as by CPU, GPU or Node. The upgrade process will default this value to `CPU`. After the upgrade process is complete, you can change this value to another acceptable value. The list of acceptable values is listed in the [Configuration Guide](configuration.md).

The `resource_specs.json` file adds new fields to specify information about GPUs included in a system. Below is an example of the new format, which includes the new GPU fields.

```json
[
    {
        "resource": "resource1",
        "start_date": "2016-12-27",
        "cpu_node_count": 400,
        "cpu_processor_count": 4000,
        "cpu_ppn": 10,
        "gpu_node_count": 0,
        "gpu_processor_count": 0,
        "gpu_ppn": 0,
        "end_date": "2017-12-01"
    }
]
```

The values for the GPU fields will default to 0 during the upgrade process. After the upgrade process, you can edit this file to include more accurate GPU information.

If you have multiple entries for a resource, please make sure the `start_date` and `end_date` for each entry are accurate. Also note that if a resource has multiple entries, you may omit the `end_date` from the last entry. The first entry for each resource needs a `start_date`; if you have not provided one, one will be automatically set based on the earliest database fact for the resource (e.g., earliest submitted job, earliest cloud VM start time, earliest storage entry start date, etc.). See the [Configuration Guide](configuration.md) for more information.

After editing either the `resources.json` or `resource_specs.json` file, `xdmod-ingestor` should be run to make sure the new information is ingested into Open XDMoD.

#### Other configuration files

During the upgrade, various configuration files are updated in
`datawarehouse.d`, `etl`, and `rawstatistics.d`.

### Database Changes

During the upgrade, the following changes will be made to database tables.

#### `mod_shredder`

- A table will be added for `staging_resource_allocation_type` and populated
  with data from the `resource_allocation_types.json` configuration file.
- The `staging_resource_config` table will have a column added for
  `resource_allocation_type_abbrev` and will be populated with data from the
  `resources.json` configuration file.
- The `staging_resource_spec` table will have the following columns renamed:
    - `nodes` → `cpu_node_count`
    - `processors` → `cpu_processor_count`
    - `ppn` → `cpu_processor_count_per_node`
- The `staging_resource_spec` table will have the following columns added:
    - `gpu_node_count`
    - `gpu_processor_count`
    - `gpu_processor_count_per_node`
    - `su_available_per_day`
    - `normalization_factor`
- The `staging_resource_type_realms` table will have its `realm`
  column changed from type `varchar(16)` to `varchar(255)`.

#### `mod_hpcdb`

The following tables will be changed and populated with data from
`mod_shredder`:

- The `hpcdb_resource_allocated` table will have its `idx_resource`
  index updated to add the `start_date_ts` column and to be a unique index.
- The `hpcdb_resource_allocation_types` table will be created.
- The `hpcdb_resource_specs` table will have the following columns renamed:
    - `node_count` → `cpu_node_count`
    - `cpu_count` → `cpu_processor_count`
    - `cpu_count_per_node` → `cpu_processor_count_per_node`
- The `hpcdb_resource_specs` table will have the following columns added:
    - `gpu_node_count`
    - `gpu_processor_count`
    - `gpu_processor_count_per_node`
    - `su_available_per_day`
    - `normalization_factor`
- The `hpcdb_resources` table will have a column added for
  `resource_allocation_type_id`.

#### `modw`

The following tables will be changed and populated with data from `mod_hpcdb`:

- A table will be created for `resource_allocation_type`.
- The `resource_allocated` table will have columns added for `start_day_id`
  and `end_day_id`.
- The `resourcefact` table will have a column added for
  `resource_allocation_type_id`.
- The `resourcespecs` table will have the following columns renamed:
    - `processors` → `cpu_processor_count`
    - `q_nodes` → `cpu_node_count`
    - `q_ppn` → `cpu_processor_count_per_node`
- The `resourcespecs` table will have the following columns added:
    - `resourcespec_id`
    - `start_day_id`
    - `end_day_id`
    - `gpu_processor_count`
    - `gpu_node_count`
    - `gpu_processor_count_per_node`
    - `su_available_per_day`
    - `su_available_per_day`
    - `last_modified`
- The `resourcespecs` table will have a unique index added containing
  `resourcespec_id`.

#### `modw_aggregates`

- New tables will be created and populated with data from `modw`:
    - `resourcespecsfact_by_day`
    - `resourcespecsfact_by_month`
    - `resourcespecsfact_by_quarter`
    - `resourcespecsfact_by_year`
    - `resourcespecsfact_by_day_resourcespecslist`

#### `moddb`
- Charts in `ReportTemplateCharts` will have their titles updated to say
  `CPU Utilization` instead of just `Utilization`.

#### `modw_cloud`
- The `modw_cloud.instance_type_union` table will have the default value for
  the `instance_type_id` column set to `NULL`.

Additional 11.0.1 Upgrade Notes
-------------------

### Database Changes

During the upgrade, the following tables in the `modw_cloud` schema:

- `session_records`
- `cloudfact_by_day`
- `cloudfact_by_month`
- `cloudfact_by_quarter`
- `cloudfact_by_year`

will have the following columns added:

- `person_organization_id`
- `piperson_organization_id`

Then, the Cloud realm will be reaggregated.

[github-latest-release]: https://github.com/ubccr/xdmod/releases/latest
[mysql-config]: configuration.md#mysql-configuration
