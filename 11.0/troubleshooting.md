---
title: Troubleshooting Guide
---

Check that all the [Software Requirements](software-requirements.html) are
installed and refer to the [Configuration Guide](configuration.html) to be sure
all the software requirements are configured correctly.

Check the list of [Frequently Asked Questions](faq.html).

Run the config checker utility:

    $ xdmod-check-config

Check the Apache logs for errors.  The Apache log files are typically
located in `/var/log/apache` or `/var/log/httpd`, but the location will
depend on your operating system and how you installed Apache.

Check the Open XDMoD logs for errors.  The Open XDMoD logs files are
located in `/var/log/xdmod` if the RPM was installed or `PREFIX/logs`
(e.g. `/opt/xdmod/logs`) if the source package was installed.

Contact us for [support](support.html).
