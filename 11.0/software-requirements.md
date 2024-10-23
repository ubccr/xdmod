---
title: Software Requirements
---

Open XDMoD requires the following software:

- [Apache][] 2.4
    - [mod_rewrite][]
    - [mod_ssl][]
    - [mod_headers][]
- [MariaDB][]/[MySQL][] 5.5.3+, MariaDB 10.3.17+
- [PHP][] 5.4+, 7.2+, (PHP 8 not supported)
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

Open XDMoD can be run on any Linux distribution, but has been tested on
CentOS 7.

Most of the requirements can be installed with the package managers
available from these distributions.

### CentOS 7

**NOTE**: The package list below includes packages included with
[EPEL](https://fedoraproject.org/wiki/EPEL).  This repository can be
added with this command for CentOS 7:

    # yum install epel-release

    # yum install httpd php php-cli php-mysql php-gd php-pdo php-xml \
                  libreoffice \
                  nodejs \
                  mariadb-server mariadb cronie logrotate \
                  perl-Image-ExifTool php-mbstring php-pecl-apcu jq \
                  chromium-headless librsvg2-tools

**NOTE**: After installing Apache and MySQL you must make sure that they
are running.  CentOS may not start these services and they will not
start after a reboot unless you have configured them to do so.

**NOTE**: APCu is optional, but highly recommended as it provides enhanced performance.

### Rocky 8+
```sh
dnf install -y epel-release

dnf install -y httpd php php-cli php-mysqlnd php-gd php-pdo php-xml \
            php-pear libreoffice nodejs \
            mariadb-server mariadb cronie logrotate \
            perl-Image-ExifTool php-mbstring php-pecl-apcu jq \
            chromium-headless librsvg2-tools
```

**NOTE**: The nodejs version that is enabled by default in Rocky 8 is nodejs 10. Open
XDMoD requires nodejs 16 which can be installed on Rocky 8 using the  nodejs 16 module
stream as follows:

```shell
dnf module -y reset nodejs
dnf module -y install nodejs:16
```

**NOTE**: The method to install the system level mongodb drivers has changed for Rocky 8. To account for this, you will
need to run the following:
```shell
dnf install -y php-devel
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
Centos 7 (PHP 5.4.16) and Rocky 8 (PHP 7.2.24).  Open XDMoD is not compatible
with PHP 8 at this time.

Some Linux distributions (including CentOS) do not set the timezone used
by PHP in their default configuration.  This will result in many warning
messages from PHP.  You should set the timezone in your `php.ini` file
by adding the following, but substituting your timezone:

```ini
date.timezone = America/New_York
```

The PHP website contains the full list of supported [timezones][].

Production Open XDMoD instances should use HTTPS, which is enabled via the webserver
configuration (see below).

[timezones]: https://secure.php.net/manual/en/timezones.php

### Apache

Production instances of Open XDMoD should use HTTPS. This requires
the `mod_ssl` module be installed and enabled. The `mod_headers` module
is also recommended so that the [HTTP Strict-Transport-Security](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Strict-Transport-Security)
header can be set on the webserver.

Open XDMoD requires that mod_rewrite be installed and enabled.  Since
the Open XDMoD portal is a web application you will also need to make
sure you have configured your firewall properly to allow appropriate
network access.

### MySQL

Open XDMoD is tested to work with MariaDB 5.5.60 and 10.3.28, and may be
compatible with more recent releases of MySQL and MariaDB.  Open XDMoD is
not compatible with MySQL 8.0 at this time.

Refer to the [Configuration Guide](configuration.html#mysql-configuration)
for configuration details.

### Chromium

Chromium is required for graph exporting.

Open XDMoD has been tested with `chromium-headless` from EPEL, `chromium` from EPEL was shown to be usable, but is not actively tested.

### SELinux

Open XDMoD does not work with the default CentOS
SELinux security policy.  You will need to disable SELinux.
