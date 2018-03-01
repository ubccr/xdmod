---
title: User/PI Names Guide
---

By default, Open XDMoD will use the Unix user names and Unix group names
used by your resource manager.  The real names of your users and PIs
must be added to Open XDMoD in a separate step.  This can be
accomplished with a CSV file and the included `xdmod-import-csv`
command.

Create a CSV file with three columns.  The first column should include
the user name or group name used by your resource manager, the second
column is the user's first name and the third column is the user's last
name.

If the user or group doesn't correspond to a person (i.e. doesn't have a
first and last name), but you want a different name to be display, leave
the second column blank and put the name in the third column.

    $ cat names.csv
    jdoe,John,Doe
    asmith,Adam,Smith
    myuser,,"My User"
    mygroup,,"My Group"
    ...

    $ xdmod-import-csv -t names -i names.csv

After you have imported the names, you will need to run the ingestor
before the names appear in the portal.

    $ xdmod-ingestor
