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
tasks.  Currently supported tasks are listed in the table below:

| Task | Example command line | Description |
| ---- | -------------------- | ----------- |
| Delete Jobs Data | `xdmod-admin --truncate --jobs` | This command removes all data from the Jobs realm.  |
| Delete Jobs Data for Resource | `xdmod-admin --jobs --delete RESOURCE_NAME` | This command removes all data for a specific resource in the Jobs Realm. |
| List configured resources | `xdmod-admin --list --resources` | This command lists all resources that are configured in the resources.json configuration file. |
| Preconfigure SSO user accounts | `xdmod-admin --users --load PATH/TO/USERSFILE.csv` | Preconfigure user account settings for SSO users. |


#### Preconfigure SSO user accounts

XDMoD can be configured to use [Single Sign On Authentication](simpleSAMLphp.md) (SSO).
When a user who has previously never used XDMoD logs in with SSO then a user account
is automatically created for them. This automatically provisioned account is created
with just the 'User' acl.

If you want a user to be able to login with SSO and have higher permissions (such as
Center Staff or Center Director), then they have to either (1) login via SSO once
so the account is created and then an admin has to update their permissions in the
admin dashboard or (2) use `xdmod-admin` to preconfigure the account _before they login for the first time_.

To preconfigure SSO accounts use the `xdmod-admin --users --load FILENAME.csv` command
toload the user settings from a csv file.
The csv file must be a comma separated csv file in utf8 character set with the following five fields:

- XDMoD Portal Username
- First Name
- Last Name
- E-mail Address
- Semi-colon separated list of ACLs (e.g. `User;Center Director`)

The allowed values for ACLs can be viewed in the "User Management" dialog in the "Admin Dashboard"
in the portal. The "XDMoD Portal Username" value must match exactly the `username` property from the SSO provider.

The `xdmod-admin` command will not make any changes to existing user accounts, so if an
account already exists, then the admin dashboard must be used to update the account
information.

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

The `acl-config` command ensures that all acl related tables are present, 
required configuration files are valid, and the acl related database tables are 
populated correctly.
