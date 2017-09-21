---
title: Open XDMoD
---

XDMoD (XD Metrics on Demand) is an NSF-funded open source tool designed
to audit and facilitate the utilization of the XSEDE cyberinfrastructure
by providing a wide range of metrics on XSEDE resources, including
resource utilization, resource performance, and impact on scholarship
and research.  The [XDMoD](https://xdmod.ccr.buffalo.edu/) framework is
designed to meet the following objectives: (1) provide the user
community with a tool to manage their allocations and optimize their
resource utilization, (2) provide operational staff with the ability to
monitor and tune resource performance, (3) provide management with a
tool to monitor utilization, user base, and performance of resources,
and (4) provide metrics to help measure scientific impact.  While
initially focused on the XSEDE program, Open XDMoD has been created to
be adaptable to any HPC environment.

The framework includes a computationally lightweight application kernel
auditing system that utilizes performance kernels chosen from both
low-level benchmarks and actual scientific and engineering applications
to measure overall system performance from the user’s perspective.  This
allows continuous resource monitoring to measure all aspects of system
performance including file-system, processor, and memory performance,
and network latency and bandwidth.  Current and past utilization
metrics, coupled with application kernel-based performance analysis, can
be used to help guide future cyberinfrastructure investment decisions,
plan system upgrades, tune machine performance, improve user job
throughput, and facilitate routine system operation and maintenance.

This work was sponsored by NSF under grant numbers
[ACI 1025159][nsf-1025159] and [ACI 1445806][nsf-1445806] for the
development of technology audit service for XSEDE.

[nsf-1025159]: http://nsf.gov/awardsearch/showAward?AWD_ID=1025159
[nsf-1445806]: http://nsf.gov/awardsearch/showAward?AWD_ID=1445806

**NOTE**: Not all of the XDMoD features mentioned above are currently
available in Open XDMoD.

For more information, questions, or feedback send email to
`ccr-xdmod-help` at `buffalo.edu`.

Want to be notified about XDMoD releases and news? Subscribe to our
[mailing list][listserv].

[listserv]: http://listserv.buffalo.edu/cgi-bin/wa?SUBED1=ccr-xdmod-list&A=1

License
-------

Open XDMoD is an open source project released under the
[GNU Lesser General Public License ("LGPL") Version 3.0][lgpl3].

[lgpl3]: http://www.gnu.org/licenses/lgpl-3.0.txt

<div markdown="1" class="non-commercial-notice">

While Open XDMoD is released under the LGPL, it is bundled with some
3<sup>rd</sup> party software libraries that are not free for commercial
use. See [License Notices][notices] for more information on
3<sup>rd</sup> party software bundled with Open XDMoD.

Specifically, Open XDMoD is bundled with [Highcharts][], released
under the [Creative Commons Attribution-NonCommercial 3.0][cc-by-nc]
license.  For more information regarding Highcharts licensing, please
refer to their
[Non Commercial Licensing FAQ][highcharts-non-commerical-faq].

</div>

[notices]:                       notices.html
[highcharts]:                    http://shop.highsoft.com/highcharts.html
[cc-by-nc]:                      http://creativecommons.org/licenses/by-nc/3.0/legalcode
[highcharts-non-commerical-faq]: https://shop.highsoft.com/faq/non-commercial
