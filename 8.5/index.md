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
following objectives: (1) provide the user community with a tool to more
effectively and efficiently use their allocations and optimize their use
of HPC resources, (2) provide operational staff with the ability to
monitor, diagnose, and tune system performance as well as measure the
performance of all applications running on their system, (3) provide
software developers with the ability to easily obtain detailed analysis
of application performance to aid in optimizing code performance, (4)
provide stakeholders with a diagnostic tool to facilitate HPC planning
and analysis, and (5) provide metrics to help measure scientific impact.
In addition, analyses of the operational characteristics of the HPC
environment can be carried out at different levels of granularity,
including job, user, or on a system-wide basis.

The Open XDMoD portal provides a rich set of features accessible through
an intuitive graphical interface, which is tailored to the role of the
user.  Metrics provided include: number of jobs, CPU hours consumed,
wait time, and wall time, with minimum, maximum and the average of
these metrics, in addition to many others.  Metrics are organized by a
customizable hierarchy appropriate for your organization.

A version of Open XDMoD, namely [XDMoD](https://xdmod.ccr.buffalo.edu/),
was developed to monitor the NSF supported portfolio of supercomputers
that fall under the [XSEDE](https://www.xsede.org/) program.

This work was sponsored by NSF under grant numbers
[ACI 1025159][nsf-1025159] and [ACI 1445806][nsf-1445806].

[nsf-1025159]: http://nsf.gov/awardsearch/showAward?AWD_ID=1025159
[nsf-1445806]: http://nsf.gov/awardsearch/showAward?AWD_ID=1445806

For more information, questions, or feedback send email to
`ccr-xdmod-help` at `buffalo.edu`.

Want to be notified about XDMoD releases and news? Subscribe to our
[mailing list][listserv].

[listserv]: http://listserv.buffalo.edu/cgi-bin/wa?SUBED1=ccr-xdmod-list&A=1

Referencing XDMoD
-----------------

When referencing XDMoD, please cite the following publication:

Jeffrey T. Palmer, Steven M. Gallo, Thomas R. Furlani, Matthew D. Jones,
Robert L. DeLeon, Joseph P. White, Nikolay Simakov, Abani K. Patra,
Jeanette Sperhac, Thomas Yearke, Ryan Rathsam, Martins Innus, Cynthia D. Cornelius,
James C. Browne, William L. Barth, Richard T. Evans,
"Open XDMoD: A Tool for the Comprehensive Management of High-Performance Computing Resources",
*Computing in Science &amp; Engineering*, Vol 17, Issue 4, 2015, pp. 52-62.
[10.1109/MCSE.2015.68](http://dx.doi.org/10.1109/MCSE.2015.68)

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
