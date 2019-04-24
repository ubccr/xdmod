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
and end date, formatted as YYYY-MM-DD,  that include the dates
associated with the modified data.

    $ xdmod-ingestor --start-date *start-date* --end-date *end-date*


Last Modified Start Date
------------------

When aggregating data use this date as the basis of what jobs to include.
Only jobs ingested on or after this date will be aggregated
This defaults to the start of the ingest and aggregation process.

    $ xdmod-ingestor --last-modified-start-date "TIME"

The value specified to `TIME` **must** be a MySQL server clock time.

Advanced Usage 
---------------

The ingestor may be set to only ingest specific realms or timeframes.  You
must also set the last modified start date for aggregation to work properly.

**Jobs:**

The following is an example of only aggregating the jobs realm.

Set timestamp:
    $ last_modified_start_date=$(date +'%F %T')

Ingest shredded jobs to staging table:

    $ xdmod-ingestor --ingest-shredded

Ingest staging table jobs to HpcDB:

    $ xdmod-ingestor --ingest-staging

Ingest all HpcDB jobs:

    $ xdmod-ingestor --ingestHpcdb

Aggregate:

    $ xdmod-ingestor --aggregate=jobs --last-modified-start-date "$last_modified_start_date"

**Cloud:**

If you do not have jobs data and/or wish to break down your ingestion process to 
exclusively ingest cloud data, you may do so as such. 

You will need to specify the type of cloud data (generic, openstack):

Set timestamp:

    $ last_modified_start_date=$(date +'%F %T')

Ingest Generic logs:

    $ xdmod-ingestor --datatype=genericcloud

Ingest Openstack logs:

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

