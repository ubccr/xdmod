---
title: Storage Metrics (Beta)
---

The Storage Realm provides metrics relating to storage subsystems including
local disk and network attached storage. Each individual storage subsystem is
treated as a separate storage resource (e.g., GPFS, Isilon, NFS, Lustre, etc.)
This provides a mechanism for tracking utilization for a single storage
resource as well as an aggregate across resources and allows for viewing this
data by mount point, department, PI/project, and user. In addition, storage
metrics can be plotted alongside data from other realms such as Job Accounting
and Job Performance.

The data currently required to provide these metrics is described below in
Input Format and are typically collected from the quota system on these storage
resource. Detailed information such as access and modification times of
individual files is not currently supported as the collection of this
information is meta-data intensive and can adversely affect the performance of
the filesystem.

**NOTE: Storage metrics are currently considered beta quality.**

These instructions use the file paths from the RPM installation.  If you've
installed from source they will need to be adjusted accordingly.

## Input Format

Storage data must be formatted in JSON files.  These files will be validated
against the JSON Schema
`/etc/xdmod/etl/etl_schemas.d/storage/usage.schema.json`.

**NOTE**: The thresholds and usage numbers are all measured in bytes.
Mountpoint names are currently limited to 255 characters.

### Input Fields

- `resource` - Storage resource name.
- `mountpoint` - File system mountpoint.
- `user` - User system username.
- `pi` - PI system username.
- `dt` - Date and time data was collected.  Must be in RFC 3339 format
  (e.g. `2017-01-01T00:00:00Z`).  Must be UTC.
- `soft_threshold` - Quota soft threshold measured in bytes.
- `hard_threshold` - Quota hard threshold measured in bytes.
- `file_count` - Number of files.
- `logical_usage` - Logical usage measured in bytes.
- `physical_usage` - Physical usage measured in bytes.

### Example

```json
[
    {
        "resource": "nfs",
        "mountpoint": "/home",
        "user": "jdoe",
        "pi": "pi_username",
        "dt": "2017-01-01T00:00:00Z",
        "soft_threshold": 1000000,
        "hard_threshold": 1200000,
        "file_count": 10000,
        "logical_usage": 100000,
        "physical_usage": 100000
    },

    ...
]
```

## Setup

### Add Storage Resource

Add a storage resource using the `xdmod-setup` script or by manually modifying
`/etc/xdmod/resources.json`.  If you use the `xdmod-setup` script be sure to
select a storage resource type or you will need to manually add the roles
configuration as described in the following section.

The resource name (also referred to as the resource code; not the formal name)
must then be used in the JSON storage input files described above.

### Update Roles Configuration

If you did not use the `xdmod-setup` script you must enable the storage query
descripters manually.  This can be done by creating a new file,
`roles.d/storage.json`, with the appropriate contents.  An example is shown
below and can be copied from `/usr/share/xdmod/templates/roles.d/storage.json`.

```json
{
    "+roles": {
        "+default": {
            "+query_descripters": [
                {
                    "realm": "Storage",
                    "group_by": "none"
                },
                {
                    "realm": "Storage",
                    "group_by": "resource"
                },
                {
                    "realm": "Storage",
                    "group_by": "resource_type"
                },
                {
                    "realm": "Storage",
                    "group_by": "mountpoint"
                },
                {
                    "realm": "Storage",
                    "group_by": "person"
                },
                {
                    "realm": "Storage",
                    "group_by": "pi"
                },
                {
                    "realm": "Storage",
                    "group_by": "username"
                },
                {
                    "realm": "Storage",
                    "group_by": "nsfdirectorate"
                },
                {
                    "realm": "Storage",
                    "group_by": "parentscience"
                },
                {
                    "realm": "Storage",
                    "group_by": "fieldofscience"
                }
            ]
        },
         "+pub": {
            "+query_descripters": [
                {
                    "realm": "Storage",
                    "group_by": "none"
                },
                {
                    "realm": "Storage",
                    "group_by": "resource"
                },
                {
                    "realm": "Storage",
                    "group_by": "resource_type"
                },
                {
                    "realm": "Storage",
                    "group_by": "mountpoint"
                },
                {
                    "realm": "Storage",
                    "group_by": "person"
                },
                {
                    "realm": "Storage",
                    "group_by": "pi"
                },
                {
                    "realm": "Storage",
                    "group_by": "username",
                    "disable": true
                },
                {
                    "realm": "Storage",
                    "group_by": "nsfdirectorate"
                },
                {
                    "realm": "Storage",
                    "group_by": "parentscience"
                },
                {
                    "realm": "Storage",
                    "group_by": "fieldofscience"
                }
            ]
        }
    }
}
```

After adding the file you must update the ACL database tables:

```
$ acl-config && acl-import
```

## Data Ingestion

Storage data is shredded and ingested using the [`xdmod-shredder`](shredder.md)
and [`xdmod-ingestor`](ingestor.md) commands. Please see their respective
guides for further information.

All of the following commands must be executed in the order specified below to
fully ingest storage data into the data warehouse.

Ingest all files in the `/path/to/storage/logs` directory:

```
$ xdmod-shredder -f storage -r resource-name -d /path/to/storage/logs
```

**NOTE**: The above command will ingest all files in the `/path/to/storage/logs`
directory even if they have already been ingested.

Ingest and aggregate data:

```
$ last_modified_start_date=$(date +'%F %T')
$ xdmod-ingestor --datatype storage
$ xdmod-ingestor --aggregate=storage --last-modified-start-date "$last_modified_start_date"
```
