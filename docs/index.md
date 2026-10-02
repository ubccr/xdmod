---
title: Open XDMoD
---

Open XDMoD is an open source tool to facilitate the management of high
performance computing resources.   It is widely deployed at academic,
industrial and governmental HPC centers.  Open XDMoD's management
capabilities include monitoring standard metrics such as utilization,
providing quality of service metrics designed to proactively identify
underperforming system hardware and software, and reporting job level
performance data for every job running on the HPC system without the
need to recompile applications.  Open XDMoD is designed to meet the
following objectives:
1. Provide the user community with a tool to more
effectively and efficiently use their allocations and optimize their use
of HPC resources;
1. Provide operational staff with the ability to
monitor, diagnose, and tune system performance as well as measure the
performance of all applications running on their system;
1. Provide
software developers with the ability to easily obtain detailed analysis
of application performance to aid in optimizing code performance;
1. Provide stakeholders with a diagnostic tool to facilitate HPC planning
and analysis;
1. Provide metrics to help measure scientific impact.
In addition, analyses of the operational characteristics of the HPC
environment can be carried out at different levels of granularity,
including job, user, or on a system-wide basis.

The Open XDMoD portal provides a rich set of features accessible through
an intuitive graphical interface, which is tailored to the role of the
user.  Metrics provided include: number of jobs, CPU hours consumed,
wait time, and wall time, with minimum, maximum and the average of
these metrics, in addition to many others.  Metrics are organized by a
customizable hierarchy appropriate for your organization.

Open XDMoD also features a [Data Analytics Framework](data-analytics-framework.md) that provides API access to the data in the portal.

The base Open XDMoD software supports monitoring
and analysis of HPC batch computing systems, OpenStack-based clouds
and compute storage. Other features are enabled via optional modules:
- Compute Job performance monitoring is enabled via the [Job Performance](https://supremm.xdmod.org) module
- HPC quality of service tracking is enabled via the [Application Kernels](https://appkernels.xdmod.org) module
- Reporting on the usage of Open OnDemand is enabled by the [Open OnDemand](https://ondemand.xdmod.org) module

Open XDMoD is the core technology behind [ACCESS XDMoD](https://xdmod.access-ci.org)
which is used to monitor the NSF-supported portfolio of advanced computing
systems and services that are integrated with the NSF-funded [ACCESS](https://access-ci.org) program.
The ACCESS XDMoD instance is based on Open XDMoD with customization and
 additional modules that support ingestion and processing of data from
multiple ACCESS-specific sources such as the ACCESS Allocations
database and NSF Award search database as well as from ACCESS resource
providers such as CloudBank and Jetstream2.

This material is based upon work supported by the National Science Foundation
under Grant Numbers [OAC 2137603][nsf-2137603],
[ACI 1025159][nsf-1025159] and [ACI 1445806][nsf-1445806].

[nsf-2137603]: https://www.nsf.gov/awardsearch/showAward?AWD_ID=2137603
[nsf-1025159]: https://www.nsf.gov/awardsearch/showAward?AWD_ID=1025159
[nsf-1445806]: https://www.nsf.gov/awardsearch/showAward?AWD_ID=1445806

For more information, questions, or feedback send email to
`ccr-xdmod-help` at `buffalo.edu`.

Want to be notified about XDMoD releases and news? Subscribe to our
[mailing list][listserv].

[listserv]: https://listserv.buffalo.edu/scripts/wa.exe?SUBED1=ccr-xdmod-list&A=1

Referencing XDMoD
-----------------

When referencing XDMoD, please cite the following publication:

Jeffrey T. Palmer, Steven M. Gallo, Thomas R. Furlani, Matthew D. Jones,
Robert L. DeLeon, Joseph P. White, Nikolay Simakov, Abani K. Patra,
Jeanette Sperhac, Thomas Yearke, Ryan Rathsam, Martins Innus, Cynthia D. Cornelius,
James C. Browne, William L. Barth, Richard T. Evans,
"Open XDMoD: A Tool for the Comprehensive Management of High-Performance Computing Resources",
*Computing in Science &amp; Engineering*, Vol 17, Issue 4, 2015, pp. 52-62.
[10.1109/MCSE.2015.68](https://doi.org/10.1109/MCSE.2015.68)

License
-------

Open XDMoD is an open source project released under the
[GNU Lesser General Public License ("LGPL") Version 3.0][lgpl3].

[lgpl3]: http://www.gnu.org/licenses/lgpl-3.0.txt

[notices]:                       notices.html
[cc-by-nc]:                      http://creativecommons.org/licenses/by-nc/3.0/legalcode
