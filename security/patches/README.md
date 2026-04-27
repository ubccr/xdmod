# Open XDMoD workaround security patch files

This directory contains workaround patch files that pertain to known security
vulnerabilities in Open XDMoD. Each patch file should contain a comment at the
top that:

1. Links to the corresponding security advisory from
   https://github.com/ubccr/xdmod/security/advisories, which should explain how
   to apply the patch, and
1. An indication of which versions of Open XDMoD to which the patch applies.

## Adding a new patch file

To add a new patch file, developers should do the following:

1. Open a Pull Request that adds the patch file to this directory.
1. Once the Pull Request is approved and merged to the `main` branch, obtain
   the SHA of the merge commit.
1. Copy the URL of the raw content of the patch file from the merge commit on
   GitHub, i.e.:
    ```
    https://raw.githubusercontent.com/ubccr/xdmod/<commit-sha>/security/patches/<patch-name>.patch
    ```
1. Include that URL as a link in the corresponding GitHub security advisory
   along with instructions how to apply the patch.
