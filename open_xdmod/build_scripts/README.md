Open Source Build Notes
=======================

Updating Schema Definition Files
--------------------------------

**NOTE**: This is currently broken...

If changes have been made directly to the database the schema files need
to be updated to keep everything synchronized.  Use this command to run
`mysqldump` for each of the databases:

    $ ./open_xdmod/build_scripts/ddl/dump_schemas.php -u xdmod -p ...

This will update the schema files in `open_xdmod/db/schema` along with
the data files in `open_source/db/data`.

**NOTE**: Migration files will need to be manually created so that the
upgrade script will be able to make the same changes to the database.

Building the Distribution Tarball
---------------------------------

Before building a release check that a new version number is set and
that the change log has been updated.

- Update version number
    - configuration/constants.php
        - "OPEN_XDMOD_VERSION" needs to be updated on prod.
    - open_xdmod/modules/xdmod/configuration/portal_settings.ini
- Update changelog
    - open_xdmod/modules/xdmod/CHANGELOG
    - open_xdmod/modules/xdmod/html/about/release_notes.html

Run the build script from the git repo:

    $ ./open_xdmod/build_scripts/build_package.php --clone \
                                                   --branch branch \
                                                   --module xdmod

This script creates a tarball after removing files specified in
`open_xdmod/modules/xdmod/build.json`.

Building the RPM Distribution
-----------------------------

Before an RPM release is built check that a new version number is set
and that the change log has been updated.

### Update spec Files

These files contain both version numbers and change logs.

- open_xdmod/modules/\*/\*.spec

**NOTE**:  See alpha/beta/rc notes below.

### Alpha, Beta and Release Candidates

There are additional changes necessary when switching creating an alpha,
beta or release candidate (or when switching back to a "normal"
release).  Note the addition\removal of `beta1` in the below
pseudo-diff:

    -Release:       0.1.beta1%{?dist}
    +Release:       1.0%{?dist}

    -Source:        %{name}-%{version}beta1.tar.gz
    -BuildRoot:     %(mktemp -ud %{_tmppath}/%{name}-%{version}beta1-%{release}-XXXXXX)
    +Source:        %{name}-%{version}.tar.gz
    +BuildRoot:     %(mktemp -ud %{_tmppath}/%{name}-%{version}-%{release}-XXXXXX)

    -%setup -q -n %{name}-%{version}beta1
    +%setup -q -n %{name}-%{version}

    -    --docdir=%{_docdir}/%{name}-%{version}beta1 \
    +    --docdir=%{_docdir}/%{name}-%{version} \

    -%{_docdir}/%{name}-%{version}beta1/
    +%{_docdir}/%{name}-%{version}/

### Build Steps

Create the tarball (see above):

    $ ./open_source/build/tar/make_dist.php

Copy spec file, patches and tarball to RPM build server:

    $ scp open_xdmod/modules/\*/\*.spec server:rpmbuild/SPECS
    $ scp open_xdmod/build/xdmod-\*.tar.gz server:rpmbuild/SOURCES

**NOTE**: Replace `server` with the name of the build server.

Build RPM file:

    $ cd rpmbuild
    $ rpmbuild -bb SPECS/xdmod.spec

Check for errors:

    $ rpmlint -i RPMS/noarch/xdmod-$VERSION.rpm
