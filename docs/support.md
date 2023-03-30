---
title: Support
---

Support Lifecycle
-----------------

In general, Open XDMoD has two major releases each year and each major version will receive support
 for one year from the time of initial release.  This includes security fixes and other critical updates in
addition to limited access to our support team.

Note that there was only one major release of Open XDMoD in 2022 due to the team supporting the
transition from the [XD Metrics Service](https://www.nsf.gov/awardsearch/showAward?AWD_ID=1445806)
 to [ACCESS Metrics](https://metrics.access-ci.org). The 10.0 release of
Open XDMoD therefore has a longer than typical support time.

| Version | Release Date       | End of Support     |
| ------- | ------------------ | ------------------ |
| 10.0    | March 10, 2022     | September 10, 2023     |

Supported Operating Systems
---------------------------

CentOS 7 is the officially supported operating system for Open XDMoD.  If you
are using a different operating system and encounter an issue, install Open
XDMoD on CentOS 7 and reproduce the issue in that environment before requesting
support.

Rocky 8 packages are available in 'beta' release.  We aim to provide the same level
 of support for Rocky 8 as we do for Centos 7, but some issues may not be fixed until
the planned 10.5 release, which will have full support for Rocky 8.

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
