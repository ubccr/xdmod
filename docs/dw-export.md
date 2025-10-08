---
title: Data Warehouse Export
---

The Data Warehouse Export feature of Open XDMoD is designed to give users
access to the raw (non-aggregate) data contained in the Open XDMoD data
warehouse.  This is achieved through an interface where users submit requests
using the web portal that are fulfilled by a batch process that runs at
a scheduled time each day.  When the data export is complete an email is
generated to notify the user that their data is ready.  If any errors occur an
email is sent to the technical support email address configured in
`portal_settings.ini`.  The data export can then be downloaded using the link
contained in the email or from the web portal.  After a configured time period
has elapsed the data export will be deleted from the server and the download
will no longer be available.

## Configuration

There are several configuration options for the Data Warehouse Export feature.
These are set in the `portal_settings.ini` file.  They can be changed manually
or using the `xdmod-setup` script.

```ini
; Configuration for data warehouse export functionality.
[data_warehouse_export]
; Exported data files will be stored in this directory.
export_directory = "/var/spool/xdmod/export"
; Length of time in days that files will be retained before automatic deletion.
retention_duration_days = 30
; Salt used during deidentification.
hash_salt = "..."
```

The directory where data files are stored is set by the `export_directory`
option.  This must be an absolute path for a directory on a file system with
sufficient storage.  The exact storage necessary will depend on how many
exports are created and the amount of data contained in the data files.  If a
large quantity of data is exported it is advised to create a separate partition
to store the data files.

The time period that data files will be retained is set by the
`retention_duration_days` option.  This specifies the number of days that a
data file will be kept in the export directory before it is removed.

A [salt][] may be specified by the `hash_salt` option that is used when hashing
data that is configured to be anonymized.  This will be set to a random value
the first time the Data Warehouse Export is configured using the `xdmod-setup`
script.

### Exported Data Configuration

Specifying which fields are exported is configured in the
`rawstatistics.d/20_jobs.json` file.  For each field listed in the `fields`
section there is a `batchExport` option that may be set to `true`, `false`, or
`"anonymize"`.  This file contains other sections and options that are used by
other features of Open XDMoD.  The other sections and options in this file
should not be changed without a thorough understanding of all the different
ways this file is used.

**NOTE**: The format of this file will likely change in the future.  If this
file is modified and the format changes in a future version the file must be
manually updated to use the new format and any changes must be re-applied.  The
[Job Performance][supremm] module uses a different format and that is also
expected to change in the future.

This is an example of the general structure of this file and the location of
the `batchExport` option:

```json
{
    ...
    "Jobs": {
        ...
        "fields": [
            {
                ...
                "batchExport": true
            },
            ...
        ]
    }
}
```

## Data Export Batch Process

Data export requests are fulfilled by a batch process that is run nightly via
cron.  The cron job is scheduled in the file `/etc/cron.d/xdmod`.  This file
may be modified to alter the schedule for the job.  The command used to
generate the data export files is `batch_export_manager.php`.  If you suspect
that there is a problem with the export process the following command may be
run by the `xdmod` user to produce debugging output:

```sh
/usr/lib64/xdmod/batch_export_manager.php --debug
```

[salt]: https://en.wikipedia.org/wiki/Salt_(cryptography)
[supremm]: https://supremm.xdmod.org/
