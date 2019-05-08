---
title: Hardware Requirements
---

Open XDMoD has few strict hardware requirements.  The hardware required to run
Open XDMoD depends on the quantity of data it will process and store as well as
the number of concurrent users.  The supported hardware configuration is a
single server that will run both the web and database services.  This may be a
physical server or a virtual machine; no containerization technologies are
supported at this time.  For security reasons it is suggested, but not
required, that no other services run on the same machine and that access is
limited to system administrators.  All end user access should be restricted to
the web portal.

For example, a virtual machine with 4 (2018) processor cores and 16GB of memory
is sufficient to serve an Open XDMoD instance with moderate user activity and
28M jobs run over 11 years.

## Processor Requirements

While a lightly used Open XDMoD installation requires only a single processor
core (the equivalent of an Intel Nehalem server processor from 2010 or better),
it is recommended to use a 64 bit x86 processor with at least two cores. XDMoD
instances supporting sites that process a large number of jobs or many
concurrent users will require additional CPU resources.

## Memory Requirements

Open XDMoD requires a minimum of 256MB of RAM.  This would be sufficient for
testing purposes, but any substantial amount of data will require more memory.
For less than 1 million job records 4GB would be acceptable.  For less than 25
million job records 16GB would be sufficient.  For over 100 million job records
64GB would be desirable.

## Storage Requirements

The uncompressed size of the Open XDMoD package is roughly 150MB and
dependencies will require additional space depending on what is already
installed on your system.  In addition, temporary space is required for various
Open XDMoD processes.  Roughly 300MB of disk space is required per each million
jobs.

## Graphics Card Requirements

Open XDMoD does not require a graphics card and will not use any GPU in your
server.
