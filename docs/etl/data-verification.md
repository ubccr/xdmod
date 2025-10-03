---
site: Verification of Data After Changes to ETL
---

## Description

Comparing changes made during ETL development can be tedious and we are trying to improve the
ability to compare table data when making changes to ETL actions. When comparing data, we can't
simply compare the number of columns, column names, data types, and exact values. In some cases
columns were added or removed, data types may have changed, values may or may not be nullable, and
there may be rounding errors or MySQL may be using an approximate-value numeric. The following
options are now available:

- `--coalesce-column` forces columns to be coalesced to a value (default 0) and improves the ability
  to compare columns that may be null in the source and/or destination tables

- `--truncate-column` truncates decimal values (default 0 places) eliminating rounding errors

- `--pct-error-column` compares the values of 2 columns by calculating the percent error and
  ensuring it is less than a threshold (default 0.01)
  
- `--autodetect-column-comparison` Attempt to automatically determine the right combination of
  options based on the source and destination column type and whether or not they are nullable.  (1)
  If either column is nullable, the values need to be coalesced to a non-null value before comparing
  and (2) If at least one column is a double/float/decimal and differs from the other column,
  truncate the columns before comparison and add the percent-difference calculation. Often times,
  the value of a double that has been calculated in an aggregate function may differ after several
  digits in the mantissa or MySQL may use scientific notation to show an approximate-value numeric
  literal.

For example, the following command will compare `federated_osg_baseline.jobfact_by_day` to
`federated_osg_etltest.jobfact_by_day` and will only compare columns present in the source table. It
will ignore the fact that there are additional columns in the destination table and will ignore the
fact that some column types were changed from `double` to `decimal(36,4)` but will take measures so
exact decimal numbers are not needed for the comparison to succeed.

```
php verify_table_data.php -s federated_osg_baseline -d federated_osg_etltest -t jobfact_by_day \
--ignore-column-count --ignore-column-type --autodetect-column-comparison -n 2
```

The following query is generated

```sql
SELECT src.*
FROM `federated_osg_baseline`.`jobfact_by_day` src
LEFT OUTER JOIN `federated_osg_etltest`.`jobfact_by_day` dest ON (src.account_id <=> dest.account_id
AND src.allocation_id <=> dest.allocation_id
AND (TRUNCATE(COALESCE(src.cpu_time, 0), 0) <=> TRUNCATE(COALESCE(dest.cpu_time, 0), 0) OR ABS((TRUNCATE(COALESCE(dest.cpu_time, 0), 0) - TRUNCATE(COALESCE(src.cpu_time, 0), 0)) / TRUNCATE(COALESCE(src.cpu_time, 0), 0)) <= 0.000100000000000)
AND src.day <=> dest.day
AND src.day_id <=> dest.day_id
AND src.fos_id <=> dest.fos_id
AND COALESCE(src.job_count, 0) <=> COALESCE(dest.job_count, 0)
AND src.jobtime_id <=> dest.jobtime_id
AND (TRUNCATE(COALESCE(src.local_charge, 0), 0) <=> TRUNCATE(COALESCE(dest.local_charge, 0), 0) OR ABS((TRUNCATE(COALESCE(dest.local_charge, 0), 0) - TRUNCATE(COALESCE(src.local_charge, 0), 0)) / TRUNCATE(COALESCE(src.local_charge, 0), 0)) <= 0.000100000000000)
AND (TRUNCATE(COALESCE(src.node_time, 0), 0) <=> TRUNCATE(COALESCE(dest.node_time, 0), 0) OR ABS((TRUNCATE(COALESCE(dest.node_time, 0), 0) - TRUNCATE(COALESCE(src.node_time, 0), 0)) / TRUNCATE(COALESCE(src.node_time, 0), 0)) <= 0.000100000000000)
AND src.nodecount_id <=> dest.nodecount_id
AND src.organization_id <=> dest.organization_id
AND src.person_id <=> dest.person_id
AND src.person_nsfstatuscode_id <=> dest.person_nsfstatuscode_id
AND src.person_organization_id <=> dest.person_organization_id
AND src.piperson_organization_id <=> dest.piperson_organization_id
AND src.principalinvestigator_person_id <=> dest.principalinvestigator_person_id
AND COALESCE(src.processorbucket_id, 0) <=> COALESCE(dest.processorbucket_id, 0)
AND src.processors <=> dest.processors
AND src.queue_id <=> dest.queue_id
AND src.resource_id <=> dest.resource_id
AND src.resourcetype_id <=> dest.resourcetype_id
AND COALESCE(src.running_job_count, 0) <=> COALESCE(dest.running_job_count, 0)
AND COALESCE(src.started_job_count, 0) <=> COALESCE(dest.started_job_count, 0)
AND COALESCE(src.submitted_job_count, 0) <=> COALESCE(dest.submitted_job_count, 0)
AND (TRUNCATE(COALESCE(src.sum_cpu_time_squared, 0), 0) <=> TRUNCATE(COALESCE(dest.sum_cpu_time_squared, 0), 0) OR ABS((TRUNCATE(COALESCE(dest.sum_cpu_time_squared, 0), 0) - TRUNCATE(COALESCE(src.sum_cpu_time_squared, 0), 0)) / TRUNCATE(COALESCE(src.sum_cpu_time_squared, 0), 0)) <= 0.000100000000000)
AND (TRUNCATE(COALESCE(src.sum_job_weights, 0), 0) <=> TRUNCATE(COALESCE(dest.sum_job_weights, 0), 0) OR ABS((TRUNCATE(COALESCE(dest.sum_job_weights, 0), 0) - TRUNCATE(COALESCE(src.sum_job_weights, 0), 0)) / TRUNCATE(COALESCE(src.sum_job_weights, 0), 0)) <= 0.000100000000000)
AND (TRUNCATE(COALESCE(src.sum_local_charge_squared, 0), 0) <=> TRUNCATE(COALESCE(dest.sum_local_charge_squared, 0), 0) OR ABS((TRUNCATE(COALESCE(dest.sum_local_charge_squared, 0), 0) - TRUNCATE(COALESCE(src.sum_local_charge_squared, 0), 0)) / TRUNCATE(COALESCE(src.sum_local_charge_squared, 0), 0)) <= 0.000100000000000)
AND (TRUNCATE(COALESCE(src.sum_node_time_squared, 0), 0) <=> TRUNCATE(COALESCE(dest.sum_node_time_squared, 0), 0) OR ABS((TRUNCATE(COALESCE(dest.sum_node_time_squared, 0), 0) - TRUNCATE(COALESCE(src.sum_node_time_squared, 0), 0)) / TRUNCATE(COALESCE(src.sum_node_time_squared, 0), 0)) <= 0.000100000000000)
AND (TRUNCATE(COALESCE(src.sum_waitduration_squared, 0), 0) <=> TRUNCATE(COALESCE(dest.sum_waitduration_squared, 0), 0) OR ABS((TRUNCATE(COALESCE(dest.sum_waitduration_squared, 0), 0) - TRUNCATE(COALESCE(src.sum_waitduration_squared, 0), 0)) / TRUNCATE(COALESCE(src.sum_waitduration_squared, 0), 0)) <= 0.000100000000000)
AND (TRUNCATE(COALESCE(src.sum_wallduration_squared, 0), 0) <=> TRUNCATE(COALESCE(dest.sum_wallduration_squared, 0), 0) OR ABS((TRUNCATE(COALESCE(dest.sum_wallduration_squared, 0), 0) - TRUNCATE(COALESCE(src.sum_wallduration_squared, 0), 0)) / TRUNCATE(COALESCE(src.sum_wallduration_squared, 0), 0)) <= 0.000100000000000)
AND (TRUNCATE(COALESCE(src.sum_weighted_expansion_factor, 0), 0) <=> TRUNCATE(COALESCE(dest.sum_weighted_expansion_factor, 0), 0) OR ABS((TRUNCATE(COALESCE(dest.sum_weighted_expansion_factor, 0), 0) - TRUNCATE(COALESCE(src.sum_weighted_expansion_factor, 0), 0)) / TRUNCATE(COALESCE(src.sum_weighted_expansion_factor, 0), 0)) <= 0.000100000000000)
AND src.systemaccount_id <=> dest.systemaccount_id
AND (TRUNCATE(COALESCE(src.waitduration, 0), 0) <=> TRUNCATE(COALESCE(dest.waitduration, 0), 0) OR ABS((TRUNCATE(COALESCE(dest.waitduration, 0), 0) - TRUNCATE(COALESCE(src.waitduration, 0), 0)) / TRUNCATE(COALESCE(src.waitduration, 0), 0)) <= 0.000100000000000)
AND (TRUNCATE(COALESCE(src.wallduration, 0), 0) <=> TRUNCATE(COALESCE(dest.wallduration, 0), 0) OR ABS((TRUNCATE(COALESCE(dest.wallduration, 0), 0) - TRUNCATE(COALESCE(src.wallduration, 0), 0)) / TRUNCATE(COALESCE(src.wallduration, 0), 0)) <= 0.000100000000000)
AND src.year <=> dest.year)
WHERE dest.account_id IS NULL
LIMIT 2
```

### Help

```
Usage: verify_table_data.php

    -h, --help
    Display this help

    -a, --autodetect-column-comparison
    Attempt to auto-detect how columns should be compared based on the source and destination column type and whether or not they are nullable.

    --coalesce-column <column>[,<value>]
    Coalesce <column> to <value> (default 0) before comparing. This is useful when comparing values that may be NULL.

    -c, --database-config
    The portal_settings.ini section to use for database configuration parameters

    -d, --dest-schema <destination_schema>
    The schema for the destination tables. If not specified the source schema will be used.

    --ignore-column-count
    Ignore the column count between tables as long as the source columns are present in the destination.

    --ignore-column-type
    Ignore the column types between tables, useful for comparing the effect of data type changes.

    -m <src>=<dest>, --map-column <src>=<dest>
    Map a column in the source table to a different column in the destination table. This is useful for testing columns that have been renamed.

    -n, --num-missing-rows <number_of_rows>
    Display this number of missing rows. If not specified, all missing rows are displayed.

    -p, --pct=error-column <column>[,error>]
    Compute the percent error between the source and destination columns and ensure that it is less than <error> (default 0.01). This is useful when comparing doubles or values that have been computed and may differ in decimal precision. See --truncate-column.

    --show-row-differences
    Show the columns that are different between source and destination rows with the same key. Ignored columns are not displayed.

    -s, --source-schema <source_schema>
    The schema for the source tables.

    -t, --table <table_name>
    -t, --table <source_table_name>=<dest_table_name>
    A table to compare between the source and destination schemas. Use the 2nd form to specify different names for the source and destination tables. Table names may also include a schema designation, in which case the default schema will not be added. May be specified multiple times.

    --truncate-column <column>[,<digits>]
    Truncate <column> to <digits> (default 0) before comparing. This is useful when comparing fractional values or squares of fractional values.

    -w, --where <where_clause_fragment>
    Add a WHERE clause to the table comparison. The table aliass "src" and "dest" refer to the source and destination tables, respectively.

    -x, --exclude-column <column>
    Exclude this column from the comparison. May be specified multiple times.

    -v, --verbosity {debug, info, notice, warning, quiet} [default notice]
    Level of verbosity to output from the ETL process
```
