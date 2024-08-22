---
title: Support
---

Support Lifecycle
-----------------

In general, Open XDMoD has one or two major releases each year, and each major version will receive support
 for one year from the time of initial release.  This includes security fixes and other critical updates in
addition to limited access to our support team.

Note that the 10.5 release has a longer than typical support window due to a later than typical release date
for 11.0.

| Version | Release Date       | End of Support    |
|---------|--------------------|-------------------|
| 10.5    | September 03, 2023 | October 31, 2024* |

**\* Note: CentOS 7 officially reached end of life on June 30, 2024, and as such so did our official support for el7 XDMoD.**

Supported Operating Systems
---------------------------

Rocky 8 and CentOS 7 are the officially supported operating systems for Open XDMoD.  If you
are using a different operating system and encounter an issue, please install Open
XDMoD on either of these operating systems and reproduce the issue in that environment before requesting
support.

Requesting Support
------------------

Before contacting us please see the list of [Frequently Asked Questions](faq.html).

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

[listserv]: https://listserv.buffalo.edu/scripts/wa.exe?SUBED1=ccr-xdmod-list&A=1
