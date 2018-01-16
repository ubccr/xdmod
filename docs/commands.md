---
title: Command Reference
---

Open XDMoD includes several command line utilities that are available to
users.

### xdmod-setup

The `xdmod-setup` command is used to configure the Open XDMoD portal and
initialize the databases used by Open XDMoD.  See the
[Configuration Guide](configuration.html) for more details.

### xdmod-upgrade

The `xdmod-upgrade` command is used to upgrade the Open XDMoD databases
and configuration files to be compatible with the currently installed
version of Open XDMoD.  See the [Upgrade Guide](upgrade.html) for more
details.

### xdmod-admin

The `xdmod-admin` command is used to perform various administrative
tasks.  Currently supported tasks include listing all the resources
configured in Open XDMoD and truncating all job data from the Open
XDMoD databases.

### xdmod-shredder

The `xdmod-shredder` command is used to load data from log files into
the Open XDMoD databases.  This command writes data to the
`mod_shredder` database.  See the [Shredder Guide](shredder.html) for more
details.

### xdmod-slurm-helper

The `xdmod-slurm-helper` command is used to load data from Slurm's
`sacct` command into the Open XDMoD databases.  See the
[Slurm Notes](resource-manager-slurm.html) for more details.

### xdmod-ingestor

The `xdmod-ingestor` command is used to prepare data that has already
been loaded into the Open XDMoD database for querying by the Open XDMoD
portal.  This command reads from the `mod_shredder` database and writes
to `mod_hpcdb`, `modw`, `modw_aggregates`, and `modw_filters` databases.
See the [Ingestor Guide](ingestor.html) for more details.

### xdmod-import-csv

The `xdmod-import-csv` command is used to load data from CSV files into
the Open XDMoD database.  This command writes data to the `mod_hpcdb`
database.  See the [User Name Guide](user-names.html) and
[Hierarchy Guide](hierarchy.html) for more details.

### xdmod-update-resource-specs

The `xdmod-update-resource-specs` command is used to update your
`resource_specs.json` file.

### xdmod-check-config

The `xdmod-check-config` command is used to check your Open XDMoD
environment for any problems.  See the
[Troubleshooting Guide](troubleshooting.html) for more details.

### xdmod-build-filter-lists

The `xdmod-build-filter-lists` command is used to build filter lists for the
different realms.  Ability to auto detect and build all or specify a specific
realm or realms to process.

### acl-config

The `acl-config` command both validates the configuration files that are used
by the ACL framework and ensures that the contents of the tables created by
`acl-xdmod-management` are populated correctly based on information in the
validated configuration files.

### acl-etl

The `acl-etl` command is used by the `acl-xdmod-management` and `acl-import`
commands.

### acl-import

The `acl-import` command imports data from the existing tables into the ACL
tables so that they start with a valid representation of the current
installation.

### acl-xdmod-management

The `acl-xdmod-management` command creates or updates the structure of the
database tables used by the ACL framework.

