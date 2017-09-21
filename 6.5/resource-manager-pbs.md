---
title: PBS Notes
---

Open XDMoD supports PBS including OpenPBS and TORQUE.

Logs Files
----------

The xdmod-shredder `-d`/`--dir` option was designed to work with the
accounting log naming convention used by PBS/TORQUE. If you are not
using the same convention (files are named `YYYYMMDD` corresponding to
the current date), do not use this option.  These log files are
typically stored in `/var/spool/pbs/server_priv/accounting`,
`$PBSROOT/server_priv/accounting`,
`/var/spool/torque/server_priv/accounting`, or
`$TORQUEROOT/server_priv/accounting`.
