---
title: Logo Image
---

You can add an image to the Open XDMoD header.  The image will appear to
the left of the "Sign Up" button.  The image can be specified during the
setup process using the `xdmod-setup` command or manually added to the
`portal_settings.ini` configuration file.  The image should have a
maximum height of 32 pixels.

The logo is specified in the `general` section of `portal_settings.ini`.
The logo is referenced by its absolute path on the file system and must
be readable by the user/group your web server is running as.

    [general]
    center_logo = /usr/share/xdmod/html/gui/images/center_logo_ccr.png
    center_logo_width = 329
