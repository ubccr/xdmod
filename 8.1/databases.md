---
title: Database Guide
---

Open XDMoD uses several MySQL databases.  These will be automatically be
automatically created by the database section of the `xdmod-setup`
command.

Manual Setup
------------

If you prefer to not give your root (or other privileged user's)
username and password to the setup command, you will need to create and
initialize the databases manually and add the database credentials to
`portal_settings.ini`.  These databases must be named as shown below.
Also, the credentials for `modw`, `modw_aggregates`, and `modw_filters`
must be the same.

**NOTE**: `modw_aggregates` and `modw_filters` aren't listed in
`portal_settings.ini`.

You can find the schema for each database in the `db/schema` directory
of the source distribution or in the `/usr/share/xdmod/db/schema`
directory if you've installed the RPM.  After you've created the
databases, intialize them with the corresponding file in this directory.
Likewise, the `db/data` directory contains the initial data needed by
Open XDMoD.

Create databases and database user:

    mysql> CREATE DATABASE mod_hpcdb;
    mysql> CREATE DATABASE mod_logger;
    mysql> CREATE DATABASE mod_shredder;
    mysql> CREATE DATABASE moddb;
    mysql> CREATE DATABASE modw;
    mysql> CREATE DATABASE modw_aggregates;
    mysql> CREATE DATABASE modw_filters;
    mysql> GRANT ALL ON mod_hpcdb.*       TO 'username'@'localhost' IDENTIFIED BY 'password';
    mysql> GRANT ALL ON mod_logger.*      TO 'username'@'localhost' IDENTIFIED BY 'password';
    mysql> GRANT ALL ON mod_shredder.*    TO 'username'@'localhost' IDENTIFIED BY 'password';
    mysql> GRANT ALL ON moddb.*           TO 'username'@'localhost' IDENTIFIED BY 'password';
    mysql> GRANT ALL ON modw.*            TO 'username'@'localhost' IDENTIFIED BY 'password';
    mysql> GRANT ALL ON modw_aggregates.* TO 'username'@'localhost' IDENTIFIED BY 'password';
    mysql> GRANT ALL ON modw_filters.*    TO 'username'@'localhost' IDENTIFIED BY 'password';

You will need to change `localhost` if you are running MySQL on a
different machine than where you installed Open XDMoD.

Initialize databases:

    $ mysql -u username -ppassword mod_hpcdb       </usr/share/xdmod/db/schema/mod_hpcdb.sql
    $ mysql -u username -ppassword mod_hpcdb       </usr/share/xdmod/db/data/mod_hpcdb.sql
    $ mysql -u username -ppassword mod_logger      </usr/share/xdmod/db/schema/mod_logger.sql
    $ mysql -u username -ppassword mod_logger      </usr/share/xdmod/db/data/mod_logger.sql
    $ mysql -u username -ppassword mod_shredder    </usr/share/xdmod/db/schema/mod_shredder.sql
    $ mysql -u username -ppassword mod_shredder    </usr/share/xdmod/db/data/mod_shredder.sql
    $ mysql -u username -ppassword moddb           </usr/share/xdmod/db/schema/moddb.sql
    $ mysql -u username -ppassword moddb           </usr/share/xdmod/db/data/moddb.sql
    $ mysql -u username -ppassword modw            </usr/share/xdmod/db/schema/modw.sql
    $ mysql -u username -ppassword modw            </usr/share/xdmod/db/data/modw.sql
    $ mysql -u username -ppassword modw_aggregates </usr/share/xdmod/db/schema/modw_aggregates.sql
    $ mysql -u username -ppassword modw_filters    </usr/share/xdmod/db/schema/modw_filters.sql

You will need to change the `username` and `password` accordingly and
add the `--host` option and/or `--port` option to the commands above if
you are not using localhost and/or the default mysql port.  Likewise,
the SQL file paths will different if the source package was installed.

Initialize ACLs:

    $ acl-xdmod-management
    $ acl-config
    $ acl-import

**NOTE**: If you installed the source tarball update your `PATH` to include the
Open XDMoD `bin` directory before running these commands.

### moddb

Application data.  Stores data used by the portal, including user data
and reports.

### modw

Data warehouse database.

### modw_aggregates

Data warehouse aggregate database.

**NOTE**: The tables in this database are dynamically generated and are
not created until the `xdmod-ingestor` command has performed
aggregation on the data in `modw`.

### modw_filters

Data warehouse filter lists.

**NOTE**: The tables in this database are dynamically generated and are
not created until the `xdmod-ingestor` command has performed
aggregation on the data in `modw`.

### mod_logger

Logger database.  Stores warnings and errors from various processes.

### mod_shredder

Shredder database.  Stores data from resource managers.

### mod_hpcdb

Intermediate storage for data that has been normalized before being
loaded into the data warehouse.
