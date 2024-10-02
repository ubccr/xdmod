---
title: Ingestor Guide
---

This guide will attempt to outline the use of the Open XDMoD ingestor
command line utility.  The ingestor is responsible for preparing data
that has already been loaded by the [shredder](shredder.html) into the
Open XDMoD databases so that is can be queried by the Open XDMoD portal.
This process also includes aggregating the data in the Open XDMoD
database to increase the performance of the queries performed by the
Open XDMoD portal.

General Usage
-------------

By default, the ingestor with process new job data entered into the
Open XDMoD database whose end times are within the past 7 days.

    $ xdmod-ingestor

The ingestor should be run after you have shredded your data.  If you
have multiple clusters, you may run the shredder multiple times followed
by a single use of the ingestor.

Start and End Date
------------------

If you have changed any data in the Open XDMoD database it is necessary
to re-ingest that data.  This can be accomplished by specifying a start
and end date, formatted as YYYY-MM-DD, that include the dates
associated with the modified data.

    $ xdmod-ingestor --start-date *start-date* --end-date *end-date*

Last Modified Start Date
------------------

When aggregating data use this date as the basis of what jobs to include.
Only jobs ingested on or after this date will be aggregated.
This defaults to the start of the ingest and aggregation process.

    $ xdmod-ingestor --last-modified-start-date *date*

The value specified for the `date` **must** be an ISO 8601 date or date and
time (e.g. "2019-01-01" or "2019-01-01 12:00:00").

Advanced Usage
--------------

The ingestor may be set to only ingest specific realms or time frames.  You
must also set the last modified start date for aggregation to work properly.

**Jobs:**

The following is an example of only aggregating the jobs realm.

Set timestamp:

    $ last_modified_start_date=$(date +'%F %T')

Ingest shredded jobs to staging table:

    $ xdmod-ingestor --ingest-shredded

Ingest staging table jobs to HPcDB:

    $ xdmod-ingestor --ingest-staging

Ingest all HPcDB jobs to the data warehouse:

    $ xdmod-ingestor --ingest-hpcdb

Aggregate:

    $ xdmod-ingestor --aggregate=job --last-modified-start-date "$last_modified_start_date"

**Cloud:**

If you do not have jobs data and/or wish to break down your ingestion process to
exclusively ingest cloud data, you may do so as such.

You will need to specify the type of cloud data (`genericcloud`, `openstack`):

Set timestamp:

    $ last_modified_start_date=$(date +'%F %T')

Ingest Generic logs:

    $ xdmod-ingestor --datatype=genericcloud

Ingest OpenStack logs:

    $ xdmod-ingestor --datatype=openstack

Aggregate:

    $ xdmod-ingestor --aggregate=cloud --last-modified-start-date "$last_modified_start_date"

**Storage:**

If you do not have jobs data and/or wish to break down your ingestion process to
exclusively ingest storage data, you may do so as such.

Set timestamp:

    $ last_modified_start_date=$(date +'%F %T')

Ingest storage logs:

    $ xdmod-ingestor --datatype=storage

Aggregate:

    $ xdmod-ingestor --aggregate=storage --last-modified-start-date "$last_modified_start_date"

**Resource Specifications:**

The source of data for the Resource Specifications realm is the `resource_specs.json` file. This
file is ingested any time `xdmod-ingestor` is run and the `--aggregate` flag is not specified. The
only step needed for this realm is to aggregate the data. If you recently ingested Jobs, Storage,
or Cloud data, you may have already set the `$last_modified_start_date` shell variable. Otherwise,
you should set the last modified start date to a time before the last time `xdmod-ingestor` was
run after you edited the `resource_specs.json` file.

Aggregate:

    $ xdmod-ingestor --aggregate=resourcespecs --last-modified-start-date "$last_modified_start_date"

Help
----

To display the ingestor help text from the command line:

    $ xdmod-ingestor -h

Verbose Output
--------------

By default the Open XDMoD ingestor only outputs what it considers to be
warnings, errors or notices. If you would like to see informational
output about what is being performed, use the verbose option:

    $ xdmod-ingestor -v

Debugging output is also available:

    $ xdmod-ingestor --debug
