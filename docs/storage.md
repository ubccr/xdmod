---
title: Storage (Beta)
---

**NOTE: Storage metrics are currently considered beta quality.**

## Setup

Add storage query descripters to public (or another) role in `roles.json`:

Note: these changes must be made directly to `roles.json` and not added to
separate file in `roles.d`.

```json
{
    "roles": {

        ...

        "pub": {

            ...

            "query_descripters": [

                ...

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

After modifying `roles.json` you must update the ACL database tables:

```
$ acl-import
```

## Input Format

Copy JSON files to `/etc/xdmod/etl/etl_data.d/storage/`.

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

Ingest a single files:

```
$ /usr/share/xdmod/tools/etl/etl_overseer.php \
    -p xdmod.staging-ingest-storage
```

Ingest and aggregate data:

```
$ /usr/share/xdmod/tools/etl/etl_overseer.php \
    -p xdmod.staging-ingest-storage \
    -p xdmod.staging-ingest-common \
    -p xdmod.hpcdb-ingest-storage \
    -p xdmod.hpcdb-ingest-common
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
