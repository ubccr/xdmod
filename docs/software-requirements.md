---
title: Software Requirements
---

Open XDMoD requires the following software:

- [Apache][] 2.4
    - [mod_rewrite][]
    - [mod_ssl][]
    - [mod_headers][]
- [MariaDB][]/[MySQL][] 5.5.3+, MariaDB 10.3.17+
- [PHP][] 7.4, (PHP 8 not supported)
    - [PDO][]
    - [MySQL PDO Driver][pdo-mysql]
    - [GD][php-gd]
    - [cURL][php-curl]
    - [DOM][php-dom]
    - [XMLWriter][php-xmlwriter]
    - [mbstring][php-mbstring]
    - [APCu][php-pecl-apcu]
- [nodejs][] 16
- [libreoffice][]
    - Only the libreoffice-writer component of libreoffice is used.
- [Chromium][]
    - `chromium-headless` is assumed, but chromium has been known to work
- [libRsvg][]
- [exiftool][]
- [cron][]
- [logrotate][]
- [MTA][] with `sendmail` compatibility (e.g. [postfix][], [exim][] or
  [sendmail][])
- [jq][]

[apache]:          https://httpd.apache.org/
[mod_rewrite]:     https://httpd.apache.org/docs/current/mod/mod_rewrite.html
[mod_ssl]:         https://httpd.apache.org/docs/current/mod/mod_ssl.html
[mod_headers]:     https://httpd.apache.org/docs/current/mod/mod_headers.html
[mariadb]:         https://mariadb.org/
[mysql]:           https://mysql.com/
[nodejs]:          https://nodejs.org/
[php]:             https://secure.php.net/
[pdo]:             https://secure.php.net/manual/en/book.pdo.php
[pdo-mysql]:       https://secure.php.net/manual/en/ref.pdo-mysql.php
[php-gd]:          https://secure.php.net/manual/en/book.image.php
[php-curl]:        https://secure.php.net/manual/en/book.curl.php
[php-dom]:         https://secure.php.net/manual/en/book.dom.php
[php-xmlwriter]:   https://secure.php.net/manual/en/book.xmlwriter.php
[php-mbstring]:    https://secure.php.net/manual/en/book.mbstring.php
[php-pecl-apcu]:   https://www.php.net/manual/en/book.apcu.php
[libreoffice]:     https://www.libreoffice.org
[chromium]:        https://www.chromium.org/Home
[librsvg]:         https://wiki.gnome.org/Projects/LibRsvg
[exiftool]:        http://www.sno.phy.queensu.ca/%7Ephil/exiftool/
[cron]:            https://en.wikipedia.org/wiki/Cron
[logrotate]:       https://linux.die.net/man/8/logrotate
[mta]:             https://en.wikipedia.org/wiki/Message_transfer_agent
[postfix]:         http://www.postfix.org/
[exim]:            https://www.exim.org/
[sendmail]:        https://www.proofpoint.com/us/open-source-email-solution
[jq]:              https://stedolan.github.io/jq/

Linux Distribution Packages
---------------------------

Open XDMoD is developed and tested with servers running Rocky 8 Linux. The Open XDMoD team
use Rocky 8 for their production Open XDMoD software instances.

Rocky 8 is the preferred Linux distribution, however Open XDMoD should be able to run on any Linux distribution
 that has the appropriate versions of the software dependencies available.

### Rocky 8+

**NOTE**: The php version that is enabled by default in Rocky 8 is php 7.2. Open XDMoD
requires php version 7.4 that [is supported until May 2029](https://access.redhat.com/support/policy/updates/rhel-app-streams-life-cycle#rhel8_full_life_application_streams).
```shell
dnf module -y reset php
dnf module -y enable php:7.4
```

The Open XDMoD RPM requires packages that are provided in EPEL.

```sh
dnf install -y epel-release

dnf install -y php make libzip-devel php-pear php-devel \
            mariadb-server mariadb
```

**NOTE**: The nodejs version that is enabled by default in Rocky 8 is nodejs 10. Open
XDMoD requires nodejs 16 which can be installed on Rocky 8 using the  nodejs 16 module
stream as follows:

```shell
dnf module -y reset nodejs
dnf module -y install nodejs:16
```

**NOTE**: The php mongodb drivers are not available as RPMs and must be installed using PECL as follows:
```shell
pecl install mongodb-1.16.2
echo "extension=mongodb.so" > /etc/php.d/40-mongodb.ini
```

You can double check that the installation was successful by running the following and confirming that
there is output:
```shell
php -i | grep mongo
```

Add the following property to the `[server]` section of `/etc/my.cnf.d/mariadb-server.cnf`
```shell
sql_mode=
```
This will instruct your database to operate in permissive SQL mode. We currently require this to be set as XDMoD still relies on
behaviors and features only present in older versions of MySQL.

Additional Notes
----------------

### PHP

Open XDMoD is tested to work with the versions of PHP that is supplied with
Rocky 8 (PHP 7.4.33).  Open XDMoD {{ page.sw_version }} is not compatible
with PHP 8.

Some Linux distributions (including Rocky 8) do not set the timezone used
by PHP in their default configuration.  This will result in many warning
messages from PHP and will cause data corruption in the XDMoD datawarehouse
if the PHP timezone is different from the OS or Mariadb timezone.

_You must set the timezone in your `php.ini` file
by adding the following, but substituting your timezone_:

```ini
date.timezone = America/New_York
```

The PHP website contains the full list of supported [timezones][].

Open XDMoD instances must use HTTPS. HTTP is not supported. HTTPS is enabled via the webserver
configuration (see below).

[timezones]: https://secure.php.net/manual/en/timezones.php

### Apache

Open XDMoD must use HTTPS. This requires
the `mod_ssl` module be installed and enabled. The `mod_headers` module
is also recommended so that the [HTTP Strict-Transport-Security](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Strict-Transport-Security)
header can be set on the webserver.

Open XDMoD requires that mod_rewrite be installed and enabled.  Since
the Open XDMoD portal is a web application you will also need to make
sure you have configured your firewall properly to allow appropriate
network access.

### MySQL

Open XDMoD is tested to work with MariaDB 10.3.39, and may be
compatible with more recent releases of MySQL and MariaDB.  Open XDMoD is
not compatible with MySQL 8.0 at this time.

Refer to the [Configuration Guide](configuration.html#mysql-configuration)
for configuration details.

### Chromium

Chromium is required for graph exporting.

Open XDMoD has been tested with `chromium-headless` from EPEL, `chromium` from EPEL was shown to be usable, but is not actively tested.

### SELinux

The default SELinux policy on Rocky 8 does not give sufficient permission
to the webserver software to perform all tasks required by Open XDMoD,
such as connecting to the MariaDB and MongoDB databases, and running the image export
tools.

It is recommended to either disable SELinux, or to follow the [RedHat SELinux](https://docs.redhat.com/en/documentation/red_hat_enterprise_linux/8/html/using_selinux/index)
documentation to create an SELinux policy for your server.
