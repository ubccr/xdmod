---
title: Storage (Beta)
---

**NOTE: Storage metrics are currently considered beta quality.**

These instructions use the file paths from the RPM installation.  If you've
installed from source they will need to be adjusted accordingly.

## Setup

### Add Storage Resource

Add a storage resource using the `xdmod-setup` script or by manually modifying
`/etc/xdmod/resources.json`.  If you use the `xdmod-setup` script be sure to
select a storage resource type or you will need to manually add the roles
configuration as described in the following section.

### Update Roles Configuration

If you did not use the `xdmod-setup` script you must enable the storage query
descripters.  This can be done by creating a new file, `roles.d/storage.json`,
with the appropriate contents.  An example is shown below and can be copied from
`/usr/share/xdmod/templates/roles.d/storage.json`.

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

## Input Format

NOTE: The thresholds and usage numbers are all measured in bytes.  Mountpoint
names are currently limited to 255 characters.

```json
[
    {
        "resource": "nfs",
        "mountpoint": "/home",
        "user": "jdoe",
        "pi": "pi_username",
        "dt": "2017-01-01T00:00:00",
        "soft_threshold": 1000000,
        "hard_threshold": 1200000,
        "file_count": 10000,
        "logical_usage": 100000,
        "physical_usage": 100000
    },

    ...
]
```

## ETL Commands

Ingest all files in the `/path/to/storage/logs` directory:

```
$ /usr/share/xdmod/tools/etl/etl_overseer.php \
    -d STORAGE_LOG_DIRECTORY=/path/to/storage/logs \
    -p xdmod.staging-ingest-storage
```

Ingest and aggregate data:

```
$ /usr/share/xdmod/tools/etl/etl_overseer.php \
    -p xdmod.staging-ingest-common \
    -p xdmod.staging-ingest-storage \
    -p xdmod.hpcdb-ingest-common \
    -p xdmod.hpcdb-ingest-storage
$ /usr/share/xdmod/tools/etl/etl_overseer.php \
    -a xdmod.hpcdb-xdw-ingest.resource \
    -a xdmod.hpcdb-xdw-ingest.field-of-science-hierarchy \
    -a xdmod.hpcdb-xdw-ingest.organization \
    -a xdmod.hpcdb-xdw-ingest.pi-person \
    -a xdmod.hpcdb-xdw-ingest.person \
    -a xdmod.hpcdb-xdw-ingest.people-under-pi \
    -a xdmod.hpcdb-xdw-ingest.principal-investigator \
    -a xdmod.hpcdb-xdw-ingest.resource-type \
    -a xdmod.hpcdb-xdw-ingest.system-account
$ /usr/share/xdmod/tools/etl/etl_overseer.php \
    -p xdmod.xdw-ingest-storage \
    -p xdmod.xdw-aggregate-storage
```

Rebuild filter lists after aggregation:

```
$ /usr/bin/xdmod-build-filter-lists -r Storage
```
