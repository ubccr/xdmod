---
title: Storage Metrics
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

These instructions use the file paths from the RPM installation.  If you've
installed from source they will need to be adjusted accordingly.

## Input Format

Storage data must be formatted in JSON files and these files must use the
`.json` file extension (e.g. `2019-01-01.json`).  These files will be validated
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
`/etc/xdmod/resources.json`.

The resource name (also referred to as the resource code; not the formal name)
must then be used in the JSON storage input files described above.

## Data Ingestion

Storage data is shredded and ingested using the [`xdmod-shredder`](shredder.html)
and [`xdmod-ingestor`](ingestor.html) commands. Please see their respective
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
