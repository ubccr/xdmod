# Federated XDMoD

XDMoD Federation utilizes [Tungsten]. This is not a hard requirement as the data could be shipped differently, but this is the fastest and easiest way to have the different databases share information.

## Definitions

The use of master, slave, and client are different depending on what part of the federation you are referencing so the following terms will be used:

- Core

  - The Federation server that contains data (jobfacts, etc...) from all members of the federation
  - Tungsten [Staging][trstaging] Server
  - Federation Master
  - Tungsten Slave

- Blade

  - XDMoD instance that contains a single copy of data (jobfacts, etc...)
  - Federation Slave
  - Tungsten Master

## [Prerequisites][trprereqs]

**TODO: Turn this into a script**

Follow directions for the tungsten prerequisites [prerequisites][trprereqs] The following settings are required for each database in the federation:

```text
-   binlog-format = row
-   log-bin-trust-function-creators = 1

    -   SET GLOBAL log_bin_trust_function_creators = 1;

-   server-id = 1

    -   each server must be different
```

### Initialize Core Database

The core database needs to have all of the same tables and structure of the blades.

Use the xdmod-setup script initialize the Core Database

#### Create SQL for Blade Initialization

Create blade databases on core

**(replacing blade\d.fqdn with the fqdn of the blade(s))**

```bash
assets/scripts/xdmod-fed-create-blade-sql.sh -b blade1.fqdn[,blade2.fqdn,...]
```

### Prepare for replication using tungsten

#### Core Server

##### Create tungsten user(s) and generate RSA key

**You really should change the password in the file before running** **TODO: options for password retrieval (SESSION or argument)**

```bash
assets/scripts/tungsten-add-user.sh
```

##### Tungsten Prerequisites (for the core)

**TODO: when building as RPM put java and ruby into deps**

```bash
assets/scripts/tungsten-prereqs.sh
```

##### Download and extract tungsten

```bash
assets/scripts/tungsten-download.sh -v 5.0.1 -r 138
```

#### Blades

##### Tungsten Prerequisites (for the blades)

```bash
assets/scripts/tungsten-prereqs.sh
```

##### Setup Tungsten user (skipping key generation)

**You really should change the password in the file before running** **TODO: options for password retrieval (SESSION or argument)**

```bash
assets/scripts/tungsten-add-user.sh -k
```

**TODO: Automate this?**

Copy the public (~tungsten/.ssh/id_rsa.pub), private key (~tungsten/.ssh/id_rsa), and authorized keys (~tungsten/.ssh/authorized_keys) from the Core server to the ~/tungsten/.ssh directory.

## Configuring tungsten for [Fan-In][trfanin] Replication

### Core Server

### Set Tungsten defaults

**The replication-password should be changed to what it was changed to in previous steps (You did change it didn't you?)** **TODO: options for password retrieval (SESSION or argument)**

```bash
assets/scripts/tungsten-set-defaults.sh
```

### Configuring the xdmodfederation service

**(replacing blade\d.fqdn with the fqdn of the blade(s))**

```bash
assets/scripts/tungsten-config-federation.sh -c core.fqdn -b blade1.fqdn[,blade2.fqdn,...]
```

### Configuring database rename

**(replacing blade\d.fqdn with the fqdn of the blade(s))**

```bash
assets/scripts/tungsten-config-blade.sh -b blade1.fqdn[,blade2.fqdn,...]
```

#### Validate and install Tungsten on federation

```bash
assets/scripts/tungsten-install.sh
```

#### Create ETL for Blade Initialization

```bash
assets/scripts/xdmod-fed-etl.sh -b blade1.fqdn[,blade2.fqdn,...] -e ./configuration/etl/ -d ./configuration/etl/
```

## Setup the Core

Bring in table contents from a blade modw.days modw.quarters modw.years modw.months modw.fieldofscience modw.fieldofscience_hiearchy modw.processor_buckets modw.nodecount modw.jobtimes

## Useful commands

### Specific Service status

```bash
sudo su - tungsten -c "/opt/continuent/tungsten/tungsten-replicator/bin/trepctl -service {blade.fqdn . and - replaced with underscore} status"
```

i.e.

```bash
sudo su - tungsten -c "/opt/continuent/tungsten/tungsten-replicator/bin/trepctl -service amun_ccr_xdmod_org status"
```

### Status of filters

```bash
sudo su - tungsten -c "/opt/continuent/tungsten/tungsten-replicator/bin/trepctl status -name stages"
```

### [Recovering from errors][trterrors]

```bash
sudo su - tungsten -c "trepctl -service {blade.fqdn . replaced with underscore} status"
# find
# pendingError           : NONE
# pendingErrorCode       : NONE
# pendingErrorEventId    : NONE
# pendingErrorSeqno      : -1
sudo su - tungsten -c "trepctl -service {blade.fqdn . replaced with underscore} online -skip-seqno <NUM>"
```

i.e.

```bash
sudo su - tungsten -c "trepctl -service {blade.fqdn . replaced with underscore} status"
# find
# pendingError           : NONE
# pendingErrorCode       : NONE
# pendingErrorEventId    : NONE
# pendingErrorSeqno      : -1
sudo su - tungsten -c "trepctl -service {blade.fqdn . replaced with underscore} online -skip-seqno <NUM>"
```

### Change Configuration of a blade

If you want to replicate more or less data

```bash
/opt/continuent/tungsten/tools/tpm update --repl-svc-extractor-filters=replicate --property=replicator.filter.replicate.ignore='mod_logger,mod_hpcdb,modw_aggregates,modw_filters,modw.jobfactstatus'
```

### Logs

```bash
tail -f /opt/continuent/tungsten/tungsten-replicator/log/* | egrep -v "BufferedFileDataInput| Protocol Received protocol heartbeat|DEBUG LogConnection|DEBUG LogFile Reading log file position"
```

[trfanin]: http://docs.continuent.com/tungsten-replicator-5.0/deployment-fanin.html
[trprereqs]: http://docs.continuent.com/tungsten-replicator-5.0/prerequisite.html
[trstaging]: http://docs.continuent.com/tungsten-replicator-5.0/prerequisite-staging.html
[trterrors]: http://docs.continuent.com/tungsten-replicator-5.0/operations-transactions-ident.html
[tungsten]: http://docs.continuent.com/tungsten-replicator-5.0/index.html
