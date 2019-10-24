---
title: Software Requirements
---

Open XDMoD requires the following software:

- [Apache][] 2.4
    - [mod_rewrite][]
- [MariaDB][]/[MySQL][] 5.5.3+
- [PHP][] 5.4+
    - [PDO][]
    - [MySQL PDO Driver][pdo-mysql]
    - [GD][php-gd]
    - [GMP][php-gmp]
    - [Mcrypt][php-mcrypt]
    - [cURL][php-curl]
    - [DOM][php-dom]
    - [XMLWriter][php-xmlwriter]
    - [PEAR MDB2 Package][pear-mdb2]
    - [PEAR MDB2 MySQL Driver][pear-mdb2-mysql]
    - [mbstring][php-mbstring]
    - [APCu][php-pecl-apcu]
- [Java][] 1.8 including the [JDK][]
- [PhantomJS][] 2.1+
- [ghostscript][] 9+
- [cron][]
- [logrotate][]
- [MTA][] with `sendmail` compatibility (e.g. [postfix][], [exim][] or
  [sendmail][])
- [jq][]

[apache]:          https://httpd.apache.org/
[mod_rewrite]:     https://httpd.apache.org/docs/current/mod/mod_rewrite.html
[mariadb]:         https://mariadb.org/
[mysql]:           https://mysql.com/
[php]:             https://secure.php.net/
[pdo]:             https://secure.php.net/manual/en/book.pdo.php
[pdo-mysql]:       https://secure.php.net/manual/en/ref.pdo-mysql.php
[php-gd]:          https://secure.php.net/manual/en/book.image.php
[php-gmp]:         https://secure.php.net/manual/en/book.gmp.php
[php-mcrypt]:      https://secure.php.net/manual/en/book.mcrypt.php
[php-curl]:        https://secure.php.net/manual/en/book.curl.php
[php-dom]:         https://secure.php.net/manual/en/book.dom.php
[php-xmlwriter]:   https://secure.php.net/manual/en/book.xmlwriter.php
[pear-mdb2]:       https://pear.php.net/package/MDB2
[pear-mdb2-mysql]: https://pear.php.net/package/MDB2_Driver_mysql
[php-mbstring]:    https://secure.php.net/manual/en/book.mbstring.php
[php-pecl-apcu]:   https://www.php.net/manual/en/book.apcu.php
[java]:            https://java.com/
[jdk]:             http://www.oracle.com/technetwork/java/javase/downloads/index.html
[phantomjs]:       http://phantomjs.org/
[ghostscript]:     https://www.ghostscript.com/
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

    # yum install httpd php php-cli php-mysql php-gd php-mcrypt \
                  gmp-devel php-gmp php-pdo php-xml \
                  php-pear-MDB2 php-pear-MDB2-Driver-mysql \
                  java-1.8.0-openjdk java-1.8.0-openjdk-devel \
                  mariadb-server mariadb cronie logrotate \
                  ghostscript php-mbstring php-pecl-apcu jq

**NOTE**: Neither the CentOS repositories nor EPEL include PhantomJS,
so that must be installed manually.  Packages are available for
[download](http://phantomjs.org/download.html) from the PhantomJS
website.

**NOTE**: After installing Apache and MySQL you must make sure that they
are running.  CentOS may not start these services and they will not
start after a reboot unless you have configured them to do so.

**NOTE**: APCu is optional, but highly recommended as it provides enhanced performance.

Additional Notes
----------------

### PHP

Some Linux distributions (including CentOS) do not set the timezone used
by PHP in their default configuration.  This will result in many warning
messages from PHP.  You should set the timezone in your `php.ini` file
by adding the following, but substituting your timezone:

    date.timezone = America/New_York

The PHP website contains the full list of supported [timezones][].

[timezones]: https://secure.php.net/manual/en/timezones.php

### Apache

Open XDMoD requires that mod_rewrite be installed and enabled.  Since
the Open XDMoD portal is a web application you will also need to make
sure you have configured your firewall properly to allow appropriate
network access.

### MySQL

MySQL 5.5.3+ is currently required for use with Open XDMoD.

Some versions of MySQL have binary logging enabled by default.  This can
be an issue during the setup process if the user specified to create the
databases does not have the `SUPER` privilege.  If binary logging is not
required you should disable it in your MySQL configuration.  If that is
not an option you can use the less safe
[log_bin_trust_function_creators][] variable.  You may also grant the
`SUPER` privilege to the user that is used to create the Open XDMoD
database.

[log_bin_trust_function_creators]: https://dev.mysql.com/doc/refman/5.5/en/replication-options-binary-log.html#option_mysqld_log-bin-trust-function-creators

**NOTE**: Open XDMoD does not support any of the strict
[Server SQL Modes][sql-mode].  You must set `sql_mode = ''` in your MySQL
server configuration.

**NOTE**: Open XDMoD uses the `GROUP_CONCAT()` sql function. The `group_concat_max_len` server system variable must be changed to 16MB from its default value of 1024 bytes. The `max_allowed_packet`
setting must be set to at least 16MB. The recommended setting in the mysql server configuration file is as follows:

```ini
[mysqld]
max_allowed_packet   = 16M
group_concat_max_len = 16M
```

[sql-mode]: https://dev.mysql.com/doc/refman/5.5/en/sql-mode.html

### PhantomJS

The recommended version is 2.1.1.

**NOTE**: PhantomJS does not work properly with the default CentOS
SELinux security policy.  You will need to disable SELinux or create a
custom policy.

#### Creating a custom SELinux Policy for PhantomJS and ghostscript

If you have already tried to generate a report and got an error with phantom JS you can use the [audit2allow][centosselinux] command to generate a policy for you

[centosselinux]: https://wiki.centos.org/HowTos/SELinux#head-faa96b3fdd922004cdb988c1989e56191c257c01

```
# grep phantomjs /var/log/audit/audit.log | audit2allow -M httpd_phantomjs
# semodule -i httpd_phantomjs.pp
```

The other way is to compile the module

```
# yum install selinux-policy-devel
# mkdir /tmp/httpd_phantomjs
# cd /tmp/httpd_phantomjs
# cat << EOF >> httpd_phantomjs
module httpd_phantomjs 1.0;

require {
	type httpd_t;
	type fonts_t;
	type ld_so_cache_t;
	type fonts_cache_t;
	class process execmem;
	class file execute;
}

#============= httpd_t ==============
allow httpd_t fonts_cache_t:file execute;
allow httpd_t fonts_t:file execute;
allow httpd_t ld_so_cache_t:file execute;

#!!!! This avc is allowed in the current policy
allow httpd_t self:process execmem;
EOF

# make -f /usr/share/selinux/devel/Makefile
# semodule -i httpd_phantomjs.pp
# rm -rf /tmp/httpd_phantomjs
```
