# Oracle Data Endpoint

The Oracle data endpoint requires that Oracle client libraries are installed and the oci8 extension
is installed into PHP. The supported method for connecting to Oracle is via the Oracle Instant
Client Libraries and the PBP OCI8 extension (installed via PECL)

## Oracle Instant Client Libraries and PHP OCI8 Module

Download and install the [Oracle Instant Client]( http://www.oracle.com/technetwork/database/features/instant-client/index-097480.html). Both the basic and sdk files are needed to install the PECL module. **You will need an Oracle Account to download the files.**

- instantclient-basic-linux.x64-12.1.0.2.0.zip
- instantclient-sdk-linux.x64-12.1.0.2.0.zip

See:

- http://php.net/manual/en/oci8.requirements.php
- http://php.net/manual/en/oci8.installation.php
- http://www.oracle.com/technetwork/database/features/instant-client/index-097480.html

Installation instructions are below, however see the following notes:

- **These instructions were tested on Ubuntu 14.04 using v12.1.0.2.0 of the Oracle libraries and
  installing from PECL. You will need to make adjustments for your specific system and version.**
- **From the [oci8 page](https://pecl.php.net/package/oci8) use `pecl install oci8` for PHP 5.7 and
  `pecl install oci8-2.0.12` to install for PHP 5.2 - PHP 5.6.**
- **When the pecl install asks for the Oracle home directory use `instantclient,/opt/oracle`.**

Install the Oracle libraries:

```
sudo -s
cd /opt
unzip instantclient-basic-linux.x64-12.1.0.2.0.zip
unzip instantclient-sdk-linux.x64-12.1.0.2.0.zip
ln -s instantclient_12_1 oracle
chmod -R g+rX,o+rX instantclient_12_1
cd instantclient_12_1
ln -s libclntsh.so.12.1 libclntsh.so
ln -s libocci.so.12.1 libocci.so
pecl install oci8
```

Set Up The PHP Module

```
sh -c 'echo extension=oci8.so > /etc/php5/cli/conf.d/oci8.ini'
sh -c 'echo extension=oci8.so > /etc/php5/apache2/conf.d/oci8.ini'
chmod 644 /etc/php5/cli/conf.d/oci8.ini
chmod 644 /etc/php5/apache2/conf.d/oci8.ini
```

Verify the installtion by running `php -i`:

```
oci8
 
OCI8 Support => enabled
OCI8 DTrace Support => disabled
OCI8 Version => 2.0.12
Revision => $Id: 020312b6429ebb9d6272ac9bc28f6dce529434b6 $
Oracle Run-time Client Library Version => 12.1.0.2.0
Oracle Compile-time Instant Client Version => 12.1
Directive => Local Value => Master Value
 
oci8.connection_class => no value => no value
oci8.default_prefetch => 100 => 100
oci8.events => Off => Off
oci8.max_persistent => -1 => -1
oci8.old_oci_close_semantics => Off => Off
oci8.persistent_timeout => -1 => -1
oci8.ping_interval => 60 => 60
oci8.privileged_connect => Off => Off
oci8.statement_cache_size => 20 => 20
```

## Configue and Test Oracle Connection

A connection or Oracle can be made using the [Easy Connect Naming Method](https://docs.oracle.com/database/121/NETAG/naming.htm#NETAG008) or using a [Local Naming Method](https://docs.oracle.com/database/121/NETAG/naming.htm#NETAG081) using a `tnsnames.ora` file.

**Note that when using the Oracle Instant Client, the `$ORACLE_HOME` environment variable is not set.**

The examples below assume the setting below, modify these for your specific configuration:
- The database server is **db.mycompany.com**
- The port is the default 1521
- The service name is **mydb**
- The database username is **scott**
- The database password is **tiger**
- The directory where the tnsnames.ora file resides is **~/oracle/tns**

### Easy Connect Method

No setup is required. Test the connection.

```
<?php
$ora = @oci_connect('scott', 'tiger, 'db.mycompany.com/mydb');

if ( FALSE === $ora ) {
    print "Error!" . PHP_EOL;
    print_r(oci_error($ora));
} else {
    print "Connected!" . PHP_EOL;
}

// Try a simple query to see what roles have been assigned

$sql = 'SELECT *FROM user_role_privs';
$stmt = oci_parse($ora, $sql);
oci_execute($stmt);
oci_fetch_all($stmt, $result);

print_r($result);

oci_free_statement($stmt);

exit(0);
?>
```

### Local Naming Method

Set up the network service names in `~/oracle/tns/tnsnames.ora`

```
datawarehouse =
   (description =
     (address = (protocol = tcp)(host = db.mycompany.com)(port = 1521))
     (connect_data =
       (service_name = mydb)))
```

Set the `TNS_ADMIN` environment variable

```
export TNS_ADMIN=~/oracle/tns
```

Test the connection

```
<?php
$ora = @oci_connect('scott', 'tiger, 'datawarehouse');

if ( FALSE === $ora ) {
    print "Error!" . PHP_EOL;
    print_r(oci_error($ora));
} else {
    print "Connected!" . PHP_EOL;
}

// Try a simple query to see what roles have been assigned

$sql = 'SELECT *FROM user_role_privs';
$stmt = oci_parse($ora, $sql);
oci_execute($stmt);
oci_fetch_all($stmt, $result);

print_r($result);

oci_free_statement($stmt);

exit(0);
?>
```

## Install PDOOCI

Enabling Oracle support for PDO in PHP is painful. The obsolete
[PECL](https://pecl.php.net/package/pdo_oci) package can be installed or the PDO_OCI module can be
compiled from source (both of which are from 2005) using the Oracle SDK, which is not an Open Source
product. The OCI8 module is actively supported, but this does not support PDO. An alternative is the
[PDOOCI](https://github.com/taq/pdooci) project that wraps OCI functions to simulate a PDO object and
calls OCI under the hood.

```
composer require taq/pdooci
```

## Test

PDOOCI

```
// Local Naming: Uses tnsnames.ora file in $TNS_ADMIN directory
$dsn = 'oci:dbname=DataWarehouse';

// Easy Connect Naming: //host[:port]/service
$dsn = 'oci:dbname=//oracle.domain.com:1521/datawarehouse'

$dbh = @new \PDOOCI\PDO($dsn, $username, $db_password);
$sql = 'SELECT *FROM user_role_privs';
$stmt = $dbh->prepare($sql);
$stmt->execute();
$result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
print_r($result);
```

Oracle DataEndpoint

```
$options = new DataEndpointOptions();
$options->type = "oracle";
$options->name = "My Infosource";
$options->config = "oracle-test";
$options->schema = "ENT";
$myendpoint = DataEndpoint::factory($options);

try {
    $endpoint->verify(true, false);
    print "Endpoint: " . $endpoint . PHP_EOL;

    $sql = 'SELECT *FROM user_role_privs';

    $result = $endpoint->getHandle()->query($sql);
    print_r($result);
} catch (Exception $e) {
    exit($e->getMessage() . PHP_EOL);
}
```
