---
title: PBS Notes
---

Open XDMoD supports PBS including OpenPBS and TORQUE.

Logs Files
----------

When using the `pbs` format, the `xdmod-shredder` `-d`/`--dir` option expects
log files to use the accounting log naming convention used by PBS/TORQUE. If
you are not using the same convention (files are named `YYYYMMDD` corresponding
to the current date), do not use this option.

The log file for the current day will be ignored (along with any files
that correspond to future dates). This is intended to prevent the
shredding of partial log files.

If the database is empty all files that meet the above constraints will
be shredded. If there is data in the database, only files dated after
the date of the most recent job will be shredded.

These log files are typically stored in
`/var/spool/pbs/server_priv/accounting`, `$PBSROOT/server_priv/accounting`,
`/var/spool/torque/server_priv/accounting`, or
`$TORQUEROOT/server_priv/accounting`.
