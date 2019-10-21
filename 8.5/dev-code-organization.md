---
title: Code Organization
---

Depending on the method used to install Open XDMoD, the location of
various files will differ.  This document will attempt to outline the
destination of these files and their location in the source tarball.

The "Source Tarball" paths refer to the relative paths of files in the
decompressed source distribution before they are installed.  The
installation process changes the paths to attempt to conform with the
[Filesystem Hierarchy Standard][fhs].  The installation process also
changes the paths used in several files to reference the location of the
installed files.

[fhs]: http://en.wikipedia.org/wiki/Filesystem_Hierarchy_Standard

The "Source Installation" paths listed below assume that `/opt/xdmod`
has been used as the installation prefix.

Non-User Executables
--------------------

- Source Tarball: `background_scripts/`
- RPM Installation: `/usr/lib/xdmod/`
- Source Installation: `/opt/xdmod/lib/`

User Executables
-----------------

- Source Tarball: `bin/`
- RPM Installation: `/usr/bin/`
- Source Installation: `/opt/xdmod/bin/`

PHP Classes
-----------

- Source Tarball: `classes/`
- RPM Installation: `/usr/share/xdmod/classes/`
- Source Installation: `/opt/xdmod/share/classes/`

Configuration Files
-------------------

- Source Tarball: `configuration/`
- RPM Installation: `/etc/xdmod`
- Source Installation: `/opt/xdmod/etc/`

The following configuration files are also installed by the RPM:

- `/etc/cron.d/xdmod`
- `/etc/httpd/conf.d/xdmod.conf`
- `/etc/logrotate.d/xdmod`

Database Files
--------------

Database schema files, data files and database migrations.

- Source Tarball: `db/`
- RPM Installation: `/usr/share/xdmod/db/`
- Source Installation: `/opt/xdmod/share/db/`

Documentation
-------------

HTML and Markdown versions of the documentation along with the license
files.

- Source Tarball: `docs/`
- RPM Installation: `/usr/share/doc/xdmod-{{ page.sw_version }}/`
- Source Installation: `/opt/xdmod/share/`

External Libraries
------------------

Various open source libraries used by Open XDMoD.

- Source Tarball: `external_libraries/`
- RPM Installation: `/usr/share/xdmod/external_libraries/`
- Source Installation: `/opt/xdmod/share/external_libraries/`

HTML
----

HTML files used by the Open XDMoD portal.

- Source Tarball: `html/`
- RPM Installation: `/usr/share/xdmod/html/`
- Source Installation: `/opt/xdmod/share/html/`

Open XDMoD Libraries
--------------------

Non-class PHP code.

- Source Tarball: `libraries/`
- RPM Installation: `/usr/share/xdmod/libraries/`
- Source Installation: `/opt/xdmod/share/libraries/`

Log Files
---------

- Source Tarball: `logs/`
- RPM Installation: `/var/log/xdmod/`
- Source Installation: `/opt/xdmod/logs/`

Report Generator Code
---------------------

Includes the Java code used to generate reports.

- Source Tarball: `reporting/`
- RPM Installation: `/usr/share/xdmod/reporting/`
- Source Installation: `/opt/xdmod/share/reporting/`

Configuration Templates
------------------------

Template files used by `xdmod-setup` to generate config files.

- Source Tarball: `templates/`
- RPM Installation: `/usr/share/xdmod/templates/`
- Source Installation: `/opt/xdmod/share/templates/`
