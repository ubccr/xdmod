# Enabling the Gateways realm

The gateways realm displays jobs information due to science gateways. It does this by incorporating jobs data in a new XDMoD database, `modw_gateways`, created for capturing gateways data. This realm is not enabled by default in the current release. These instructions show you how to enable and populate the gateways realm in your Open XDMoD installation.

## 1. Database configuration

The gateways realm displays jobs information on science gateways for an Open XDMoD installation. It incorporates jobs that already exist in your Open XDMoD database. To do so, you will indicate the desired gateways jobs in the ingestion step indicated in b. below.

### etl_overseer.php

The database configuration steps are accomplished by running the script `etl_overseer.php` with
different options. You should specify this script's full path when calling it (RPM install location
is `/usr/share/xdmod/tools/etl/`). This script accepts numerous parameters described in the help
output:

`$ /usr/share/xdmod/tools/etl/etl_overseer.php --help`

### a. Bootstrap the gateways realm

This first step of Gateways database configuration creates the modw_gateways database, and some needed tables.

Command:

`$ /usr/share/xdmod/tools/etl/etl_overseer.php -p gateways.bootstrap`

Verify:

Successful execution results in creation of the modw_gateways database schema with empty enduser, gateway, gatewayfact_by_day_joblist, and job_metadata tables.

### b. Select and ingest jobs data for the modw_gateways database
XDMoD needs a mechanism to identify HPC jobs that were run via a gateway. The default mechanism is to use the last name associated with the user account that ran the jobs. Instructions for configuring the names are in the (User/PI Names guide)[user-names.md]. The steps to use this mechanism are:
1) Choose a suitable identifier to be used for a gateway (such as 'Gateway Proxy User')
1) Update the last name entries in `names.csv` for each gateway proxy user account.
1) Import the updated `names.csv`

The ingestion step supplies the name of the community user (gateway proxy user) that gateways use to run jobs on your resource, and joins it to the `modw.person` table. This means that the jobs submitted by that community user will be aggregated for the Gateways realm. You can change this query to match your needs, such as specifying multiple community usernames, or restricting a different column from the person table.

To change the specification of gateways jobs to aggregate, edit the file `etl/etl_action_defs.d/gateways/gateway.json`, and supply a different where clause. The current where clause in the file is:

```
        "where": [
            "p.last_name = '${community-user}'"
```

In its current state, the WHERE condition accepts as a parameter the gateway community user's `last_name`. The command below specifies community user 'Gateway proxy user'; replace this with an appropriate `last_name` from your `modw.person` table.

Command:

`$ /usr/share/xdmod/tools/etl/etl_overseer.php -p gateways.ingest -d community-user='Gateway proxy user'`

Verify:

Successful execution results in population of `modw_gateways.gateway` table with records selected from `modw.person` according to the where clause.

### c. Aggregate the data

This step creates and populates the joblist, day, month, quarter, and year tables for the gateways realm so that queries over these time periods run quickly.

Command:

`$ /usr/share/xdmod/tools/etl/etl_overseer.php -m '2001-01-01' -y '2022-01-01' -p gateways.aggregate`

where:
* -p is "process-section"
* -m is "last-modified-start-date"
* -y is "last-modified-end-date"

The last-modified-end-date should be interpreted as the date the target records were last changed; it should be greater or equal than the current date. For full aggregation, use 2001-01-01 for -m, or some other date that precedes all jobs in your database.

Verify:

Successful execution results in creation and population of the `modw_gateways.gatewayfact*` tables containing aggregated job fact and joblist data.

Running these steps populates the modw_gateways database needed for the Gateways realm.

### d. Check the modw_gateways schema

Querying the Open XDMoD database will now show the modw_gateways schema, containing the following tables and data:

Table Name | Description
-----------|-------------
enduser | empty, reserved for future development
gateway | one record per gateway (from modw.Person)
gatewayfact_by_day | jobs aggregated by day
gatewayfact_by_day_joblist | joblist by day
gatewayfact_by_month | jobs aggregated by month
gatewayfact_by_quarter | jobs aggregated by quarter
gatewayfact_by_year| jobs aggregated by year
job_metadata | empty, reserved for future development


## 2. Edit resource_types.json

The resource configuration file at `/etc/xdmod/resource_types.json` must be edited to include the Gateways realm (data sources).  This change configures the UI to display the Gateways realm and its metrics.

You may add the Gateways realm anyplace the Jobs realm is specified in the file. At a minimum, add the string "Gateways" to the HPC object, as follows:

```
{
    "resource_types": {
        "HPC": {
            "description": "High-performance computing",
            "realms": [
                "Jobs",
                "Gateways"
            ]
        },
...
}
```
### 3. Run acl-config

This step enables the UI to display the new Gateways realm that you configured in `resource_types.json`.

Command:

`$ acl-config`

### 4. Verify

To verify the new realm is present, refresh XDMoD in the browser. You should now see:

- Usage tab includes Gateways Summary metrics in the Metrics listing.
- These Gateways usage metrics with your data can be selected, plotted, and drilled down
- Gateways metrics are available for plotting, drilldown, etc. in Metric Explorer
- Following two drilldowns, Show Raw Data is available for Gateways data, enabling selection and review of job accounting data in the Job Viewer tab.
- Gateways data are available for export in the Data Export tab.

## Future work

Further work is planned to enhance the Gateways realm. It includes:

- automate this process of installing the gateways realm
- incorporate additional job metadata into the gateways schema
- incorporate gateway enduser metadata into the gateways realm
- enable search and drill-down by gateway enduser and other gateways metadata
