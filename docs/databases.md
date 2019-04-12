---
title: Database Guide
---

Open XDMoD uses several MySQL databases.  These will be
automatically created by the database section of the `xdmod-setup`
command.

Manual Setup
------------

**NOTE**: Manual setup instructions are provided for informational purposes only; no guarantee of support is provided.

If you prefer to not give the root (or any other privileged) user's
credentials to the `xdmod-setup` command, you will need to create and
initialize the databases manually and add the database credentials to
`portal_settings.ini`. These databases must be named as shown below.
Also, the credentials for `modw`, `modw_aggregates`, and `modw_filters`
must be the same.

**NOTE**: `modw_aggregates` and `modw_filters` aren't listed in
`portal_settings.ini`.

Create databases and database user:

    mysql> CREATE DATABASE mod_hpcdb;
    mysql> CREATE DATABASE mod_logger;
    mysql> CREATE DATABASE mod_shredder;
    mysql> CREATE DATABASE moddb;
    mysql> CREATE DATABASE modw;
    mysql> CREATE DATABASE modw_cloud;
    mysql> CREATE DATABASE modw_aggregates;
    mysql> CREATE DATABASE modw_filters;
    mysql> GRANT ALL ON mod_hpcdb.*       TO 'username'@'localhost' IDENTIFIED BY 'password';
    mysql> GRANT ALL ON mod_logger.*      TO 'username'@'localhost' IDENTIFIED BY 'password';
    mysql> GRANT ALL ON mod_shredder.*    TO 'username'@'localhost' IDENTIFIED BY 'password';
    mysql> GRANT ALL ON moddb.*           TO 'username'@'localhost' IDENTIFIED BY 'password';
    mysql> GRANT ALL ON modw.*            TO 'username'@'localhost' IDENTIFIED BY 'password';
    mysql> GRANT ALL ON modw_cloud.*      TO 'username'@'localhost' IDENTIFIED BY 'password';
    mysql> GRANT ALL ON modw_aggregates.* TO 'username'@'localhost' IDENTIFIED BY 'password';
    mysql> GRANT ALL ON modw_filters.*    TO 'username'@'localhost' IDENTIFIED BY 'password';

You must also run the following SQL:

    mysql> GRANT TRIGGER, DROP, INDEX, CREATE, INSERT,
    SELECT, DELETE, UPDATE, CREATE VIEW, SHOW VIEW,
    ALTER, SHOW DATABASES, CREATE TEMPORARY TABLES,
    CREATE ROUTINE, ALTER ROUTINE, EVENT, RELOAD, FILE,
    CREATE TABLESPACE, PROCESS, REFERENCES,
    LOCK TABLES ON *.* TO 'username'@'localhost';

You will need to change `localhost` if you are running MySQL on a
different machine than where you installed Open XDMoD.

Initialize databases:

    $ mysql -u username -ppassword mod_logger      </usr/share/xdmod/db/schema/mod_logger.sql
    $ mysql -u username -ppassword mod_logger      </usr/share/xdmod/db/data/mod_logger.sql

Bootstrap and populate tables:

    $ /usr/share/xdmod/tools/etl/etl_overseer.php -p xdmod.shredder-bootstrap
    $ /usr/share/xdmod/tools/etl/etl_overseer.php -p xdmod.xdb-bootstrap
    $ /usr/share/xdmod/tools/etl/etl_overseer.php -p xdmod.hpcdb-bootstrap
    $ /usr/share/xdmod/tools/etl/etl_overseer.php -p xdmod.staging-ingest-common
    $ /usr/share/xdmod/tools/etl/etl_overseer.php -p xdmod.jobs-xdw-bootstrap


**NOTE**: If you installed from source, you will need to use the
Open XDMoD `share` directory as opposed to the one above.

You will need to change the `username` and `password` accordingly and
add the `--host` option and/or `--port` option to the commands above if
you are not using localhost and/or the default mysql port.  Likewise,
the SQL file paths will different if the source package was installed.

Initialize ACLs:

    $ acl-config

**NOTE**: If you installed the source tarball update your `PATH` to include the
Open XDMoD `bin` directory before running the above command.

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
