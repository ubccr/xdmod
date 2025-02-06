#!/bin/bash
# This script is intended to be used to run tests that validate the correct
# packaging / install behavior of XDMoD. For example checking the
# file permissions are correct on the RPM packaged files.

exitcode=0
BASEDIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
REPODIR=`realpath $BASEDIR/../../`
INSTALL_DIR=/usr/share/xdmod

# Check that there are no development artifacts installed in the RPM
if rpm -ql xdmod | fgrep .eslintrc.json; then
    echo "Error eslintrc files found in the RPM"
    exitcode=1
fi

for file in open_xdmod/build/*.tar.gz;
do
    echo "Checking $file"
    if tar tf "${file}" | fgrep .eslintrc.json; then
        echo "Error eslintrc files found in build tarball $file"
        exitcode=1
    fi
done

# Check that the unknown resource is in the database with key 0
unkrescount=$(echo 'SELECT COUNT(*) from resourcetype WHERE id = 0 and abbrev = '"'"'UNK'"'" | mysql -N modw)
if [[ "${unkrescount}" -ne 1 ]]
then
    echo "Missing / inconsistent 'UNK' row in modw.resourcetype"
    exitcode=1
fi

# Check that the various scripts have not left any files around in the tmp
# directory
FIND_CRITERIA=(-type f -newer $INSTALL_DIR/html/index.php '(' -user xdmod -o -user apache ')')
if find /tmp "${FIND_CRITERIA[@]}" | grep -q  .
then
    echo "Unexpected files found in temporary directory"
    find /tmp "${FIND_CRITERIA[@]}" -print0 | xargs -0 ls -la
    exitcode=1
fi

# ensure tables absent
tblcount=$(echo 'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '"'"'moddb '"'"' AND table_name = '"'"'ReportTemplateACL'"'" | mysql -N information_schema)
if [[ "${tblcount}" -ne 0 ]]
then
    echo "Extraneous tables found in database"
fi

if ! mysqldump -d moddb report_template_acls | grep -q "ON DELETE CASCADE"
then
    echo "Missing/incorrect foreign key constraint on report_template_acls table"
    exitcode=1
fi

# Confirm that xdmod-check-config finds the RPM installation details
if ! xdmod-check-config | grep -q 'RPM Installed Packages'
then
    echo "Missing RPM information from xdmod-check-config"
    exitcode=1

fi

# Confirm that the user manual is built
# Check if table of contents tree properly links
# to corresponding file and each html file exists
MANUAL_DIR="$INSTALL_DIR"/html/user_manual
PAGES=($(grep -o '<li class="toctree-l1"><a class="reference internal" href="[^"]*"' "$MANUAL_DIR/index.html" | sed -E 's/.*href="([^"]*)"/\1/'))
for PAGE in "${PAGES[@]}"; do
    HTML_FILE="$MANUAL_DIR/${PAGE}"
    if [[ ! -f "$HTML_FILE" ]]; then
        echo "$HTML_FILE does not exist."
        exitcode=1
    fi
done

# Check if user manual is being properly hosted by the webserver
MANUAL_URL=https://localhost:443/user_manual/index.html
if [ !$(curl -I -s -o /dev/null -w "%{http_code}" -k $MANUAL_URL) == "200" ];
then
    echo "Non 200 response from $MANUAL_URL"
    exitcode=1
fi

if !(curl -s -k https://localhost:443/user_manual/index.html | grep -q h1)
then
    echo "User manual pages unavailable at $MANUAL_URL"
    exitcode=1
fi

# Check if webserver responds to https://xdmod
if !(curl -s -k https://xdmod | grep -q xdmodviewer.init)
then
    echo "Webserver did not respond to https://xdmod"
    exitcode=1
fi

# Check that the xdmod-admin command is present and can be used to add users
if !(xdmod-admin --users --load $REPODIR/tests/artifacts/xdmod/validate/sso_users.csv --force)
then
    echo "xdmod-admin load users failed"
    exitcode=1
fi

# Check the new user was added
ssousercount=$(echo 'SELECT count(*) FROM Users u LEFT JOIN UserTypes ut ON ut.id = u.user_type WHERE username = '"'"'ndent'"'"' AND first_name='"'"'Nörbert'"'"' AND ut.type = '"'"'Single Sign On'"'" | mysql -N moddb)

# Check the user as usr and cd role
rolecount=$(echo 'select COUNT(*) from Users u LEFT JOIN user_acls ua ON ua.user_id = u.id LEFT JOIN acls a ON a.acl_id = ua.acl_id WHERE u.username = '"'"'ndent'"'"' AND a.name IN ('"'"'usr'"'"', '"'"'cd'"'" | mysql -N moddb)

if [[ "${ssousercount}" -ne 1 ]]
then
    echo "xdmod-admin load user: error user account did not get added."
    exitcode=1
fi

if [[ "${rolecount}" -ne 2 ]]
then
    echo "xdmod-admin load user: error user account did not get correct acls."
    exitcode=1
fi

exit $exitcode
