---
title: Support
---

Support Lifecycle
-----------------

Releases of Open XDMoD will receive support for one year from the time of their
initial release.  This includes security fixes and other critical updates in
addition to limited access to our support team.

| Version | Release Date       | End of Support     |
| ------- | ------------------ | ------------------ |
| 10.0    | March 10, 2022     | March 10, 2023     |
| 9.5     | May 21, 2021       | May 21, 2022       |

Supported Operating Systems
---------------------------

CentOS 7 is the officially supported operating system for Open XDMoD.  If you
are using a different operating system and encounter an issue, install Open
XDMoD on CentOS 7 and reproduce the issue in that environment before requesting
support.

Requesting Support
------------------

Email `ccr-xdmod-help` at `buffalo.edu` for support.  Please include the following in your support request. Failure to include this information may delay support.

- Open XDMoD version number
- Operating system and version where Open XDMoD is installed
- The output of `xdmod-check-config`
- PHP and MySQL version (e.g, the output from `php --version`, `mysql --version`, and the SQL command `SHOW VARIABLES LIKE "%version%";`)
- The output of `php -i`
- Any messages in the XDMoD exceptions log `/var/log/xdmod/exceptions.log`
- A description of the problem you are experiencing
- Detailed steps to reproduce the problem

If the problem is observed when trying to access the web portal then please also include any
messages in the web server error log `/var/log/xdmod/apache-error.log`

Mailing List
------------

Subscribe to our [mailing list][listserv] to stay up to date with the
latest Open XDMoD release.

[listserv]: http://listserv.buffalo.edu/cgi-bin/wa?SUBED1=ccr-xdmod-list&A=1
