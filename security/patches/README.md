# Open XDMoD workaround security patch files

This directory contains workaround patch files that pertain to known security
vulnerabilities in Open XDMoD. Each patch file should contain a comment at the
top that includes:

1. A link to the corresponding GitHub security advisory, which should explain
   how to apply the patch, and
1. An indication of which versions of Open XDMoD to which the patch applies.

## Adding a new patch file

To add a new patch file, developers should do the following:

1. Name the file using the format:
    ```
    <GitHub security advisory ID>-<Open XDMoD versions affected>.patch
    ```
    e.g.,
    ```
    GHSA-29qm-7w4v-43fw-9_5_0-11_0_2.patch
    ```
    or
    ```
    GHSA-3hfh-m242-8rmh-pre_11_0_3.patch
    ```
   Or if a CVE has already been assigned, it can replace the GitHub security
   advisory ID, i.e.,:
    ```
    CVE-<CVE number>-<Open XDMoD versions affected>.patch
    ```
1. Open a Pull Request that adds the patch file to this directory.
1. Once the Pull Request is approved and merged to the `main` branch, obtain
   the SHA of the merge commit.
1. Copy the URL of the raw content of the patch file from the merge commit on
   GitHub, i.e.:
    ```
    https://raw.githubusercontent.com/ubccr/xdmod/<commit-sha>/security/patches/<patch-name>.patch
    ```
1. Include that URL as a link in the corresponding [GitHub security
   advisory](https://github.com/ubccr/xdmod/security/advisories) along with
   instructions how to apply the patch.
