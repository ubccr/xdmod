---
title: Hierarchy Guide
---

Open XDMoD supports a three level hierarchy that can be customized and
populated with site specific data.  Importing this data is a two step
process.  First, you must import the hierarchy items and then you must
import a mapping from groups (PIs) to hierarchy items.

Configuring the Hierarchy
-------------------------

Before importing your data, you can choose how you want the hierarchy
levels displayed.  You can either use the `xdmod-setup` script or edit
`hierarchy.json` directly.  The contents of that file look like this:

    {
       "top_level_label": "Hierarchy Top Level",
       "top_level_info": "Top level description",
       "middle_level_label": "Hierarchy Middle Level",
       "middle_level_info": "Middle level description",
       "bottom_level_label": "Hierarchy Bottom Level",
       "bottom_level_info": "Bottom level description"
    }

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

And imported with a command like this:

    $ xdmod-import-csv -t hierarchy -i hierarchy.csv

After importing the hierarchy it is necessary to provide a mapping from
your user groups to the hierarchy items.  The input format of this
mapping is a CSV file where the first column contains the name of groups
used by your resource manager and the second column contains names of
items in the bottom most hierarchy level you have imported.

Continuing the example above, for each group a department must be
specified:

    "group1","dept1"
    "group2","dept1"
    "group3","dept1"
    "group4","dept2"
    "group5","dept2"
    ...

And imported with a command like this:

    $ xdmod-import-csv -t group-to-hierarchy -i group-to-hierarchy.csv

After importing this data you must ingest it for the date range of any
job data you have already shredded.

    $ xdmod-ingest --start-date 2012-01-01 --end-date 2012-12-31

Disabling Hierarchy Dimensions
------------------------------

If you do not use the hierarchy feature and do not want those dimensions
to be listed in the Usage and Metric Explorer tabs, you can change your
configuration files to do so.

The easiest way to do this is to disable the dimensions in `roles.json`.
Remove these entries from the file:

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

These correspond to the three levels of the hierarchy.  The names refer
to those used by XSEDE, but the text displayed to overridden by the
names in `hierarchy.json`.  The top level is `nsfdirectorate`, the
middle level is `parentscience` and the bottom level is
`fieldofscience`.
