---
title: Database Guide
---

Open XDMoD uses several MySQL databases.  These will be automatically be
automatically created by the database section of the `xdmod-setup`
command.

Manual Setup
------------

Manual setup of the Open XDMoD database is not supported at this time.

Databases
---------

### moddb

Application data.  Stores data used by the portal, including user data
and reports.

### modw

Data warehouse database.

### modw_aggregates

Data warehouse aggregate database.

**NOTE**: The tables in this database are dynamically generated and are
not created until the `xdmod-ingestor` command has performed
aggregation on the data in `modw`.

### modw_filters

Data warehouse filter lists.

**NOTE**: The tables in this database are dynamically generated and are
not created until the `xdmod-ingestor` command has performed
aggregation on the data in `modw`.

### mod_logger

Logger database.  Stores warnings and errors from various processes.

### mod_shredder

Shredder database.  Stores data from resource managers.

### mod_hpcdb

Intermediate storage for data that has been normalized before being
loaded into the data warehouse.
