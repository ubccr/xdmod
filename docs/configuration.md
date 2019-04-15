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
- Java path
- Javac path
- PhantomJS path
- Header logo (see [Logo Image Guide](logo-image.html) for details)

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

**NOTE**: If your database is on a different server than the server where Open
XDMoD is installed you must create the databases manually.  Likewise, if you
don't want to use this process and would prefer to manually create the
databases, see the [Database Guide](databases.html).

#### Acl Database Setup / Population

This step will run immediately after you have setup the database that XDMoD will
be using and does not require any additional input. It is responsible for creating
and populating the tables required by the Acl framework.

If your XDMoD Installation requires modifications to the acl tables
(etc/etl/etl_tables.d/acls/xdmod/<table>.json) then running this step again or
the `acl-config` bin script is required.

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

Uses port 8080 by default, if changed, must also be changed in
`portal_settings.ini`.

    Listen 8080
    <VirtualHost *:8080>
        DocumentRoot /usr/share/xdmod/html
        <Directory /usr/share/xdmod/html>
            Options FollowSymLinks
            AllowOverride All
            DirectoryIndex index.php
            # Apache 2.4 access controls.
            <IfModule mod_authz_core.c>
                Require all granted
            </IfModule>
        </Directory>
        <Directory /usr/share/xdmod/html/rest>
            RewriteEngine On
            RewriteRule (.*) index.php [L]
        </Directory>
    </VirtualHost>

We recommend that you use HTTPS in production.  This will require
additional configuration.

    Listen 443
    <VirtualHost *:443>

        # Customize this section using your SSL certificate.
        SSLEngine on
        SSLCertificateFile    /etc/ssl/certs/ssl-cert-snakeoil.pem
        SSLCertificateKeyFile /etc/ssl/private/ssl-cert-snakeoil.key
        <FilesMatch "\.(cgi|shtml|phtml|php)$">
            SSLOptions +StdEnvVars
        </FilesMatch>

        DocumentRoot /usr/share/xdmod/html
        <Directory /usr/share/xdmod/html>
            Options FollowSymLinks
            AllowOverride All
            DirectoryIndex index.php
            # Apache 2.4 access controls.
            <IfModule mod_authz_core.c>
                Require all granted
            </IfModule>
        </Directory>
        <Directory /usr/share/xdmod/html/rest>
            RewriteEngine On
            RewriteRule (.*) index.php [L]
        </Directory>
    </VirtualHost>

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
- Java path
- PhantomJS path
- Header logo (see [Logo Image Guide](logo-image.html) for details)
- Database configuration

### datawarehouse.json

Defines realms, group bys, statistics.

### etl/etl_data.d/jobs/xdw/processor-buckets.json

Defines the ranges used for number of processors/cores in "Job Size"
charts.  Sites may want to align the bucket sizes with the number of
cores per node on their resources.

    [
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

### roles.json

Defines roles and the modules and statistics that each role grants
access to. The dimensions roles are associated with are also defined
here.

By default, there is a public role (`pub`) for users that have not
signed in and several other roles that apply to authenticated users.
There is also a `default` role that is used as a basis of all the other
roles.  The other roles are user (`usr`), center director (`cd`),
principal investigator (`pi`), center staff (`cs`) and manager (`mgr`).

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
            }
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

### organization.json

Defines the organization name and abbreviation.

    {
        "name": "Example Organization",
        "abbrev": "EO"
    }

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

    [
        {
            "resource": "resource1",
            "name": "Resource 1",
            "description": "Our first HPC resource",
            "resource_type_id": 1
        },
        {
            "resource": "resource2",
            "name": "Resource 2",
            "resource_type_id": 2,
            "pi_column": "account_name"
        },
        {
            "resource": "resource3",
            "name": "Resource 3",
            "resource_type_id": 1,
            "timezone": "US/Eastern",
            "shared_jobs": true
        }
    ]

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


### resource_types.json

Defines resource types.  Each resource in `resources.json` should reference a
resource type from this file.

    [
        {
            "id": 0,
            "abbrev": "UNK",
            "description": "Unknown"
        },
        {
            "id": 1,
            "abbrev": "HPC",
            "description": "High-performance computing"
        },
        ...
    ]

### update_check.json

Determines if Open XDMoD will automatically check for updates.  Set
`"enabled": false` to disable.

    {
        "enabled": true,
        "name": "John Doe",
        "organization": "Acme Widgets",
        "email": "j.doe@example.com"
    }
