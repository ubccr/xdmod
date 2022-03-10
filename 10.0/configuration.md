---
title: Configuration Guide
---

Setup Script
------------

Open XDMoD includes a setup script to help you configure your
installation.  This script will prompt you for information needed to
configure Open XDMoD and update your configuration files accordingly.
If you have modified your configuration files manually, be sure to make
backups before running this command:

    # xdmod-setup

### General Settings

The general settings include:

- Site address (The URL you will use to access Open XDMoD)
- Email address (The email address Open XDMoD will use when sending
  emails)
- Chromium path
- Header logo (see [Logo Image Guide](logo-image.html) for details)
- Whether to enable the Dashboard tab (see the [Dashboard Guide](dashboard.html) for details)

These settings are stored in `portal_settings.ini`.

### Database Settings

Will create and initialize database as well as storing these settings:

- Database hostname
- Database port number
- Database username
- Database password

These settings are stored in `portal_settings.ini`.

You will be required to supply a username and password for a user that
has privileges to create databases and users.

#### ACL Database Setup / Population

This step will run immediately after you have set up the database that Open XDMoD will
be using and does not require any additional input. It is responsible for creating
and populating the tables required by the ACL framework.

If your Open XDMoD Installation requires modifications to the ACL tables
(`/etc/xdmod/etl/etl_tables.d/acls/<table>.json`) then running this step
again or the `acl-config` bin script is required.

### Organization Settings

The organization settings require a name and abbreviation for your
organization.  These will be used in the portal to refer to anything
relating to your organization's data.

### Resources

For each resource you will need this information:

- Resource name - A short name or abbreviation that will be used when
  displaying data about specific resources.
- Formal name - A possibly longer, more descriptive name for the
  resource.
- Resource type - The type that best describes this resource.
- Node count - The current number of nodes in the resource.
- Processor count - The total sum of all the processors (CPU cores) in
  the resource.

For example, if you have a resource dedicated to your physics department
with 100 nodes that have 16 cores in each node, you could use these
values:

- Resource name: physics
- Formal name: Physics Department Cluster
- Resource type: hpc
- Node count: 100
- Processor count: 1600

The resource name supplied here must be specified during the shredding
process.  If you are using the Slurm helper script, this name must match
the cluster name used by Slurm.

The resource type defines metadata that can be used to group and filter resources
in the XDMoD user interface.

The number of nodes and cores in your resource are used to display the
utilization charts (the percentage of your cluster that is being used).
If these numbers are not accurate, these charts will likewise be
inaccurate.  If the number of nodes or processors in any of your
resources changes, you will need to update your configuration.  Refer to
the `resource_specs.json` section below for details.

### Create Admin User

This will allow you to create an administrative user that can log into
the Open XDMoD portal and create other users.  You will need to supply a
username and password for this user along with the first name, last name
and email address of your admin.

### Hierarchy

Open XDMoD allows you to define a three level hierarchy that can be used
to define various entities or groups and associate users with a group in
the hierarchy.  These can be decanal units and their associated
departments or any hierarchy that is desired.  If defined, this
hierarchy is used to generate charts that aggregate usage metrics into
groups based on users assigned to one of the groups.

See the [Hierarchy Guide](hierarchy.html) for more details.

Apache Configuration
--------------------

A template Apache configuration file is provided. The path is `/usr/share/xdmod/templates/apache.conf`
in the RPM install and `share/templates/apache.conf` in the source code install.
This template file must be copied to the Apache configuration directory and
edited to update site specific configuration settings.

For CentOS 7 and RHEL 7 the template file should be copied to `/etc/httpd/conf.d/xdmod.conf`
For other Linux distributions consult the distribution documentation
to determine the path to the webserver configuration files.

This template file must be modified to update site specific settings:

Valid SSL certificates will need to be installed and configured.  The template
configuration file must be edited to specify the path to the SSL certificate
file and SSL certificate key file. Refer to the [Apache SSL documentation](https://httpd.apache.org/docs/2.4/ssl/)
for SSL configuration information.

The `ServerName` setting should be updated to match the server name in the SSL
certificate.

The name and port of the server must match with the `site_address` and `user_manual`
configuration settings in `portal_settings.ini`.

The template configuration file also configures the webserver to send the `Strict-Transport-Security` HTTP Header
to indicate to  web browsers that the Open XDMoD instance should only be accessed using HTTPS.

```apache
<VirtualHost *:443>
    # The ServerName and ServerAdmin parameters should be updated.
    ServerName localhost
    ServerAdmin postmaster@localhost

    # Production Open XDMoD instances should use HTTPS
    SSLEngine on

    # Update the SSLCertificateFile and SSLCertificateKeyFile parameters
    # to the correct paths to your SSL certificate.
    SSLCertificateFile /etc/pki/tls/certs/localhost.crt
    SSLCertificateKeyFile /etc/pki/tls/private/localhost.key

    <FilesMatch "\.(cgi|shtml|phtml|php)$">
        SSLOptions +StdEnvVars
    </FilesMatch>

    # Use HTTP Strict Transport Security to force client to use secure connections only
    Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"

    DocumentRoot /usr/share/xdmod/html

    <Directory /usr/share/xdmod/html>
        Options FollowSymLinks
        AllowOverride All
        DirectoryIndex index.php

        <IfModule mod_authz_core.c>
            Require all granted
        </IfModule>
    </Directory>

    <Directory /usr/share/xdmod/html/rest>
        RewriteEngine On
        RewriteRule (.*) index.php [L]
    </Directory>

    ## SimpleSAML Single Sign On authentication.
    #SetEnv SIMPLESAMLPHP_CONFIG_DIR /etc/xdmod/simplesamlphp/config
    #Alias /simplesaml /usr/share/xdmod/vendor/simplesamlphp/simplesamlphp/www
    #<Directory /usr/share/xdmod/vendor/simplesamlphp/simplesamlphp/www>
    #    Options FollowSymLinks
    #    AllowOverride All
    #    <IfModule mod_authz_core.c>
    #        Require all granted
    #    </IfModule>
    #</Directory>

    # Update the path to rotatelogs if it is different on your system.
    ErrorLog "|/usr/sbin/rotatelogs -n 5 /var/log/xdmod/apache-error.log 1M"
    CustomLog "|/usr/sbin/rotatelogs -n 5 /var/log/xdmod/apache-access.log 1M" combined
</VirtualHost>
```

MySQL Configuration
-------------------

Open XDMoD does not support any of the strict [Server SQL Modes][sql-mode].
You must set `sql_mode = ''` in your MySQL server configuration.

Open XDMoD uses the `GROUP_CONCAT()` SQL function. The `group_concat_max_len`
server system variable must be changed to 16MB from its default value of 1024
bytes.

The `max_allowed_packet` setting must be set to at least 16MB.

Some versions of MySQL have binary logging enabled by default.  This can be an
issue during the setup process if the user specified to create the databases
does not have the `SUPER` privilege.  If binary logging is not required you
should disable it in your MySQL configuration.  If that is not an option you
can use the less safe [log_bin_trust_function_creators][] variable.  You may
also grant the `SUPER` privilege to the user that is used to create the Open
XDMoD database.

We recommend setting `innodb_buffer_pool_size` to around 50% of the memory on your server.

Whatever your set for `innodb_buffer_pool_size`, make sure `innodb_log_file_size`
is 25% of `innodb_buffer_pool_size`.

The recommended settings in the MySQL server configuration file are as follows:

```ini
[mysqld]
sql_mode = ''
max_allowed_packet = 1G
group_concat_max_len = 16M
innodb_stats_on_metadata = off
innodb_file_per_table = On
```

### Enabling InnoDB File Per Table setting

We recommend setting `innodb_file_per_table = On` for your Open XDMoD instance but it
is not required. This setting helps to control the size of the database files and
provides a minor speed up for InnoDB tables. It is important to note that setting
`innodb_file_per_table` to `On` is a global setting that will affect all databases
on the database server not just Open XDMoD related databases.

While not mandatory, when changing the `innodb_file_per_table` to `innodb_file_per_table = On`
we recommend that you export, drop, and re-import all Open XDMoD InnoDB tables in order
to make sure existing InnoDB data is moved to one file per table.

A script, `/bin/xdmod-convert-innodb-fpt`, is provided to help with this process.
This script will only convert Open XDMoD related databases. For any non-Open XDMoD databases
with InnoDB tables on your server you will need to export the tables manually, drop
the tables and then load them back in.

The steps to enable the `innodb_file_per_table` MySQL option and making sure
existing InnoDB data is moved to the appropriate file by using the `xdmod-convert-innodb-fpt`
script is listed below.

1. Export all Open XDMoD InnoDB tables. This can be done with the following command
`xdmod-convert-innodb-fpt --export-tables --dir=path/to/dir`
2. Drop all Open XDMoD InnoDB tables. This can be done using the `--drop-tables` flag,
`xdmod-convert-innodb-fpt --drop-tables`. This command will drop your Open XDMoD InnoDB tables!
Please make sure you have either run the command `xdmod-convert-innodb-fpt --export-tables --dir=path/to/dir`
or have manually exported the InnoDB tables before running it.
3. Shutdown the MySQL server
4. Add the following line to `/etc/my.cnf` file.
   ```ini
   innodb_file_per_table = On
   ```
5. Delete the `ibdata1`, `ib_logfile0` and `ib_logfile1` files from the MySQL data directory.
The default location for this is `/var/lib/mysql`
6. Restart MySQL
7. Import Open XDMoD InnoDB data previously exported. This can be done with the following command:
`xdmod-convert-innodb-fpt --import-tables --dir=path/to/dir`

Logrotate Configuration
-----------------------

A logrotate config file is included for the Open XDMoD log files.

Cron Configuration
------------------

A cron config file is included that runs the script that sends out
scheduled reports.  You can also use this file to schedule shredding and
ingestion.

    # Every morning at 3:00 AM -- run the report scheduler
    0 3 * * * xdmod /usr/bin/php /usr/lib/xdmod/report_schedule_manager.php >/dev/null

    # Shred and ingest PBS logs
    0 1 * * * xdmod /usr/bin/xdmod-shredder -q -r resource-name -f pbs -d /var/spool/pbs/server_priv/accounting && /usr/bin/xdmod-ingestor -q

Location of Configuration Files
-------------------------------

The Open XDMoD config files (excluding the apache, logrotate and cron
files) are located in the `etc` directory of the installation prefix or
`/etc/xdmod` for the RPM distribution.

### portal_settings.ini

Primary configuration file. Contains:

- Site address (The URL you will use to access Open XDMoD)
- Email address (The email address Open XDMoD will use when sending
  emails)
- Chromium path
- Header logo (see [Logo Image Guide](logo-image.html) for details)
- Database configuration
- Integration settings (see [Integrations](integrations.html) for details)

### datawarehouse.json

Defines realms, group bys, statistics.

### etl/etl_data.d/jobs/xdw/processor-buckets.json

Defines the ranges used for number of processors/cores in "Job Size"
charts.  Sites may want to align the bucket sizes with the number of
cores per node on their resources.

```json
[
    ["id", "min_processors", "max_processors", "description"],
    [1,       1,          1, "1"],
    [2,       2,          2, "2"],
    [3,       3,          4, "3 - 4"],
    [4,       5,          8, "5 - 8"],
    [5,       9,         16, "9 - 16"],
    [6,      17,         32, "17 - 32"],
    [7,      33,         64, "33 - 64"],
    [8,      65,        128, "65 - 128"],
    [9,     129,        256, "129 - 256"],
    [10,    257,        512, "257 - 512"],
    [11,    513,       1024, "513 - 1024"],
    [12,   1025,       2048, "1k - 2k"],
    [13,   2049,       4096, "2k - 4k"],
    [14,   4097, 2147483647, "> 4k"]
]
```

After changing this file it must be re-ingested and all job data must be
re-aggregated.  If the job data are not re-aggregated the new labels will be
displayed, but will not be accurate if the corresponding bucket has changed.

```sh
/usr/share/xdmod/tools/etl/etl_overseer.php -a xdmod.jobs-xdw-bootstrap.processorbuckets
xdmod-ingestor --aggregate=job --last-modified-start-date 1970-01-01
```

### etl/etl_data.d/jobs/xdw/gpu-buckets.json

Defines the ranges used for the number of GPUs in "GPU Count" charts.  Sites
may want to align the bucket sizes with the number of GPUs per node on their
resources.

```json
[
    ["id", "min_gpus", "max_gpus", "description"],
    [1,       0,           0, "0"],
    [2,       1,           1, "1"],
    [3,       2,           2, "2"],
    [4,       3,           3, "3"],
    [5,       4,           4, "4"],
    [6,       5,           5, "5"],
    [7,       6,           6, "6"],
    [8,       7,           7, "7"],
    [9,       8,           8, "8"],
    [10,      9,          16, "9 - 16"],
    [11,      17,         32, "17 - 32"],
    [12,      33,         64, "33 - 64"],
    [13,      65,        128, "65 - 128"],
    [14,     129,        256, "129 - 256"],
    [15,     257,        512, "257 - 512"],
    [16,     513,       1024, "513 - 1024"],
    [17,    1025,       2048, "1k - 2k"],
    [18,    2049,       4096, "2k - 4k"],
    [19,    4097, 2147483647, "> 4k"]
]
```

After changing this file it must be re-ingested and all job data must be
re-aggregated.  If the job data are not re-aggregated the new labels will be
displayed, but will not be accurate if the corresponding bucket has changed.

See the section above for commands that can be used to re-ingest and
re-aggregate the data.

### roles.json

Defines roles and the modules and statistics that each role grants
access to. The dimensions roles are associated with are also defined
here.

By default, there is a public role (`pub`) for users that have not
signed in and several other roles that apply to authenticated users.
There is also a `default` role that is used as a basis of all the other
roles.  The other roles are user (`usr`), center director (`cd`),
principal investigator (`pi`), center staff (`cs`) and manager (`mgr`).

```json
{
    "roles": {
        "default": {
            "permitted_modules": [
                {
                    "name": "tg_summary",
                    "default": true,
                    "title": "Summary",
                    "position": 100,
                    "javascriptClass": "XDMoD.Module.Summary",
                    "javascriptReference": "CCR.xdmod.ui.tgSummaryViewer",
                    "tooltip": "Displays Summary Information",
                    "userManualSectionName": "Summary Tab"
                },
                ...
            ],
            "query_descripters": [
                {
                    "realm": "Jobs",
                    "group_by": "none"
                },
                ...
            ],
            "summary_charts": [
                ...
            ],
        },
        "usr": {
            "extends": "default",
            "dimensions": [
                "person"
            ]
        },
        "cd": {
            "extends": "default",
            "dimensions": [
                "provider"
            ]
        },
        "pi": {
            "extends": "default",
            "dimensions": [
                "pi"
            ]
        },
        "cs": {
            "extends": "default",
            "dimensions": [
                "provider"
            ]
        },
        "mgr": {
            "extends": "default",
            "dimensions": [
                "person"
            ]
        }
    }
}
```

### organization.json

Defines the organization name and abbreviation.

```json
{
    "name": "Example Organization",
    "abbrev": "EO"
}
```

### resources.json

Defines resource names and types.  Each object in the array represents
the configuration for a single resource.

Optionally, allows specifying a column in the resource specific job
table to identify the PI.  The column names that may be used with this
feature must exist in the corresponding `shredded_job_*` table (e.g.
`shredded_job_pbs`, `shredded_job_slurm`) of the `mod_shredder` database
for the resource manager you are using.

For example, to use accounts from PBS/TORQUE you must use
`"pi_column": "account"`, but to use accounts from Slurm you must use
`"pi_column": "account_name"`.

The `"shared_jobs"` option indicates that the resource allows multiple
to share compute nodes. This information is used by the Job Performance
Data (SUPReMM) module to determine which HPC jobs shared compute nodes.
The default is that resources are assumed to not allow node sharing.  If
the SUPReMM module is in use and a resource does allow node sharing then
this should be set to `true`.

For cloud resources the timezone is not used and times are converted to
the local timezone that the server is in.

```json
[
    {
        "resource": "resource1",
        "name": "Resource 1",
        "description": "Our first HPC resource",
        "resource_type": "HPC"
    },
    {
        "resource": "resource2",
        "name": "Resource 2",
        "resource_type": "HPC",
        "pi_column": "account_name"
    },
    {
        "resource": "resource3",
        "name": "Resource 3",
        "resource_type": "HPC",
        "timezone": "US/Eastern",
        "shared_jobs": true
    },
    {
        "resource": "resource4",
        "name": "Resource 4",
        "resource_type": "Cloud"
    }
]
```

### resource_specs.json

Defines resource node and processor counts.  Each object in the array
represents a resource's specifications for a given time interval.  If
the number of nodes and processors in a resource have changed over time,
multiple entries are required for that resource to calculate an accurate
utilization metric.

Note that if there is a single entry for a resource, both the
`start_date` and `end_date` may be omitted.  If a resource has multiple
entries, the `start_date` may be omitted from the first and `end_date`
may be omitted from the last.

It is also possible to change the utilization metric by specifying a
percent allocated (see `percent_allocated` below).  The utilization will
then be normalized against this percentage.  This allows you to specify
the total number of nodes and processors in a resource, but force the
utilization percentage to be displayed as if only a fraction of those
processors are allocated to the jobs stored in the Open XDMoD data
warehouse.  If this data is omitted, it is assumed that the resource is
100% allocated.

```json
[
    {
        "resource": "resource1",
        "nodes": 64,
        "processors": 1024,
        "ppn": 16
    },
    {
        "resource": "resource2",
        "end_date": "2013-12-31",
        "nodes": 32,
        "processors": 256,
        "ppn": 8
    },
    {
        "resource": "resource2",
        "start_date": "2014-01-01",
        "end_date": "2014-01-15",
        "nodes": 64,
        "processors": 512,
        "ppn": 8,
        "percent_allocated": 100
    }
    {
        "resource": "resource2",
        "start_date": "2014-01-16",
        "nodes": 65,
        "processors": 520,
        "ppn": 8,
        "percent_allocated": 90
    }
]
```


### resource_types.json

Defines resource types and associates resource types with realms.  Each
resource in `resources.json` should reference a resource type from this file.
This file typically should not be changed.

### update_check.json

Determines if Open XDMoD will automatically check for updates.  Set
`"enabled": false` to disable.

```json
{
    "enabled": true,
    "name": "John Doe",
    "organization": "Acme Widgets",
    "email": "j.doe@example.com"
}
```

[log_bin_trust_function_creators]: https://dev.mysql.com/doc/refman/5.5/en/replication-options-binary-log.html#option_mysqld_log-bin-trust-function-creators
[sql-mode]: https://dev.mysql.com/doc/refman/5.5/en/sql-mode.html
