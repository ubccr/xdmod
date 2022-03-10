---
title: Hierarchy Guide
---

Open XDMoD supports a three level hierarchy that can be customized and
populated with site specific data.  Importing this data is a two step
process.  First, you must import the hierarchy items and then you must
import a mapping from groups (PIs) to hierarchy items.

For job records the source of PI data depends on the resource manager used and
is configurable.  Refer to the [`resources.json` configuration
section](configuration.html#resourcesjson) for more details.

Configuring the Hierarchy
-------------------------

Before importing your data, you can choose how you want the hierarchy
levels displayed.  You can either use the `xdmod-setup` script or edit
`hierarchy.json` directly.  The contents of that file look like this:

```json
{
   "top_level_label": "Hierarchy Top Level",
   "top_level_info": "Top level description",
   "middle_level_label": "Hierarchy Middle Level",
   "middle_level_info": "Middle level description",
   "bottom_level_label": "Hierarchy Bottom Level",
   "bottom_level_info": "Bottom level description"
}
```

In the example below the top level is "Unit", the middle level is
"Division" and the bottom level is "Department".

Importing Data
--------------

The input format of the hierarchy data is a CSV file where the first
column contains the name of the hierarchy item, the second column
contains an abbreviation of the first column and the third column
contains the parent hierarchy item (leave this column blank for the
top level hierarchy items.

For a hierarchy such as this:

    Unit 1
    ├─ Division 1
    │  ├─ Department 1
    │  └─ Department 2
    └─ Division 2
       ├─ Department 3
       └─ Department 4
    Unit 2
    └─ Division 3
       ├─ Department 5
       └─ Department 6

The input CSV would look like this:

```csv
"unit1","Unit 1",""
"unit2","Unit 2",""
"div1","Division 1","unit1"
"div2","Division 2","unit1"
"div3","Division 3","unit2"
"dept1","Department 1","div1"
"dept2","Department 2","div1"
"dept3","Department 3","div2"
"dept4","Department 4","div2"
"dept5","Department 5","div3"
"dept6","Department 6","div3"
```

And imported with a command like this:

```
$ xdmod-import-csv -t hierarchy -i hierarchy.csv
```

After importing the hierarchy it is necessary to provide a mapping from
your user groups to the hierarchy items.  The input format of this
mapping is a CSV file where the first column contains the name of groups
used by your resource manager and the second column contains names of
items in the bottom most hierarchy level you have imported.

Continuing the example above, for each group a department must be
specified:

```csv
"group1","dept1"
"group2","dept1"
"group3","dept1"
"group4","dept2"
"group5","dept2"
...
```

And imported with a command like this:

```
$ xdmod-import-csv -t group-to-hierarchy -i group-to-hierarchy.csv
```

After importing the hierarchy data you must re-ingest and re-aggregate any job,
storage, or cloud data for the date range you have already shredded.  If you
have tens of millions of records you should run the `xdmod-ingestor` command
multiple times with smaller date ranges to prevent database time-outs.

This example would re-shred and re-aggregate all data for the year 2012:

```
$ xdmod-ingestor --start-date 2012-01-01 --end-date 2012-12-31
```

Reset Hierarchy Data
--------------------

If you decided to remove the hierarchy data, or if you would like to replace
your current hierarchy with a different one, a manual process is currently
required.

Run the following queries in your MySQL database:

```
mysql> UPDATE mod_hpcdb.hpcdb_requests SET primary_fos_id = 1;
mysql> DELETE FROM mod_hpcdb.hpcdb_fields_of_science WHERE field_of_science_id != 1;
```

After that you can import a new hierarchy and mapping if desired.  Then
re-ingest and re-aggregate as done in the above section to update the data
warehouse.

Disabling Hierarchy Dimensions
------------------------------

If you do not use the hierarchy feature and do not want those dimensions
to be listed in the Usage and Metric Explorer tabs, you can change your
configuration files to do so.

To disable the dimensions, remove their definitions from `roles.json`,
`roles.d/jobs.json`, `roles.d/cloud.json`, `roles.d/storage.json` and any other
roles configuration files you may be using.

Remove these entries from the files:

```json
{
    "realm": "Jobs",
    "group_by": "nsfdirectorate"
},
{
    "realm": "Jobs",
    "group_by": "parentscience"
 },
{
    "realm": "Jobs",
    "group_by": "fieldofscience"
},
```

(These may appear multiple times and will reference the corresponding realm.)

Then run `acl-config`.

These correspond to the three levels of the hierarchy.  The names refer
to those used by XSEDE, but the text displayed to overridden by the
names in `hierarchy.json`.  The top level is `nsfdirectorate`, the
middle level is `parentscience` and the bottom level is
`fieldofscience`.
