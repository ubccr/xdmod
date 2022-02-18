#!/bin/bash
# Bootstrap script that either sets up a fresh XDMoD test instance or upgrades
# an existing one.  This code is only designed to work inside the XDMoD test
# docker instances. However, since it is designed to test a real install, the
# set of commands that are run would work on a real production system.

BASEDIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
REF_SOURCE=`realpath $BASEDIR/../artifacts/xdmod/referencedata`
REPODIR=`realpath $BASEDIR/../../`
REF_DIR=/var/tmp/referencedata

function copy_template_httpd_conf {
    cp /usr/share/xdmod/templates/apache.conf /etc/httpd/conf.d/xdmod.conf
}

if [ -z $XDMOD_REALMS ]; then
    export XDMOD_REALMS=jobs,storage,cloud
fi

cp -r $REF_SOURCE /var/tmp/

set -e
set -o pipefail

# Detect what version of CentOS we're running in, if it's 8 then we need to do some rejiggering of the yum repos to
# make things work. From: https://stackoverflow.com/questions/70926799/centos-through-vm-no-urls-in-mirrorlist
OS_VERSION=$(grep VERSION_ID < /etc/os-release  | cut -d"=" -f 2 | tr -d '"')
case "$OS_VERSION" in
    "8")
        sed -i 's/mirrorlist/#mirrorlist/g' /etc/yum.repos.d/CentOS-Linux-*
        sed -i 's|#baseurl=http://mirror.centos.org|baseurl=http://vault.centos.org|g' /etc/yum.repos.d/CentOS-Linux-*
        ;;
esac

if [ "$XDMOD_TEST_MODE" = "fresh_install" ];
then
    rpm -qa | grep ^xdmod | xargs yum -y remove || true
    rm -rf /etc/xdmod

    rm -rf /var/lib/mysql && mkdir -p /var/lib/mysql
    yum -y install ~/rpmbuild/RPMS/*/*.rpm
    copy_template_httpd_conf
    ~/bin/services start
    mysql -e "CREATE USER 'root'@'gateway' IDENTIFIED BY '';
    GRANT ALL PRIVILEGES ON *.* TO 'root'@'gateway' WITH GRANT OPTION;
    FLUSH PRIVILEGES;"

    # TODO: Replace diff files with hard fixes
    # Modify integration sso tests to work with cloud realm
    if [ "$XDMOD_REALMS" = "cloud" ]; then
        if ! patch --dry-run -Rfsup1 --directory=$REPODIR < $BASEDIR/diff/SSOLoginTest.php.diff >/dev/null; then
            # -- Fix users searched in SSO test
            patch -up1 --directory=$REPODIR < $BASEDIR/diff/SSOLoginTest.php.diff
        fi
    else
        if patch --dry-run -Rfsup1 --directory=$REPODIR < $BASEDIR/diff/SSOLoginTest.php.diff >/dev/null; then
            # -- Reverse previous patch
            patch -R -up1 --directory=$REPODIR < $BASEDIR/diff/SSOLoginTest.php.diff
        fi
    fi

    expect $BASEDIR/scripts/xdmod-setup-start.tcl | col -b

    if [[ "$XDMOD_REALMS" == *"jobs"* ]]; then
        expect $BASEDIR/scripts/xdmod-setup-jobs.tcl | col -b
    fi
    if [[ "$XDMOD_REALMS" == *"storage"* ]]; then
        expect $BASEDIR/scripts/xdmod-setup-storage.tcl | col -b
    fi
    if [[  "$XDMOD_REALMS" == *"cloud"* ]]; then
        expect $BASEDIR/scripts/xdmod-setup-cloud.tcl | col -b
    fi

    expect $BASEDIR/scripts/xdmod-setup-finish.tcl | col -b


    xdmod-import-csv -t hierarchy -i $REF_DIR/hierarchy.csv
    xdmod-import-csv -t group-to-hierarchy -i $REF_DIR/group-to-hierarchy.csv

    if [[ "$XDMOD_REALMS" == *"jobs"* ]];
    then
        for resource in $REF_DIR/*.log; do
            sudo -u xdmod xdmod-shredder -r `basename $resource .log` -f slurm -i $resource;
        done
    fi

    if [[ "$XDMOD_REALMS" == *"cloud"* ]];
    then
        last_modified_start_date=$(date +'%F %T')
        sudo -u xdmod xdmod-shredder -r openstack -d $REF_DIR/openstack -f openstack
        sudo -u xdmod xdmod-shredder -r nutsetters -d $REF_DIR/nutsetters -f openstack
        sudo -u xdmod xdmod-shredder -r openstack -d $REF_DIR/openstack_resource_specs -f cloudresourcespecs
        sudo -u xdmod xdmod-ingestor

        sudo -u xdmod xdmod-import-csv -t cloud-project-to-pi -i $REF_DIR/cloud-pi-test.csv
        sudo -u xdmod xdmod-shredder -r openstack -d $REF_DIR/openstack_error_sessions -f openstack
        sudo -u xdmod xdmod-ingestor  --last-modified-start-date "$last_modified_start_date"
    fi

    if [[ "$XDMOD_REALMS" == *"storage"* ]];
    then
        for storage_dir in $REF_DIR/storage/*; do
            sudo -u xdmod xdmod-shredder -f storage -r $(basename $storage_dir) -d $storage_dir
        done
        last_modified_start_date=$(date +'%F %T')
        sudo -u xdmod xdmod-ingestor --datatype storage
        sudo -u xdmod xdmod-ingestor --aggregate=storage --last-modified-start-date "$last_modified_start_date"
    fi

    sudo -u xdmod xdmod-import-csv -t names -i $REF_DIR/names.csv
    sudo -u xdmod xdmod-ingestor
    php $BASEDIR/scripts/create_xdmod_users.php

fi

if [ "$XDMOD_TEST_MODE" = "upgrade" ];
then
    yum -y install ~/rpmbuild/RPMS/*/*.rpm

    copy_template_httpd_conf
    sed -i 's#http://localhost:8080#https://localhost#' /etc/xdmod/portal_settings.ini

    ~/bin/services start

    # TODO: Replace diff files with hard fixes
    # Modify integration sso tests to work with cloud realm
    if [ "$XDMOD_REALMS" = "cloud" ]; then
        if ! patch --dry-run -Rfsup1 --directory=$REPODIR < $BASEDIR/diff/SSOLoginTest.php.diff >/dev/null; then
            # -- Fix users searched in SSO test
            patch -up1 --directory=$REPODIR < $BASEDIR/diff/SSOLoginTest.php.diff
        fi
    else
        if patch --dry-run -Rfsup1 --directory=$REPODIR < $BASEDIR/diff/SSOLoginTest.php.diff >/dev/null; then
            # -- Reverse previous patch
            patch -R -up1 --directory=$REPODIR < $BASEDIR/diff/SSOLoginTest.php.diff
        fi
    fi

    expect $BASEDIR/scripts/xdmod-upgrade.tcl | col -b

    if [[ "$XDMOD_REALMS" == *"cloud"* ]];
    then
        sudo -u xdmod xdmod-shredder -r openstack -d $REF_DIR/openstack -f openstack -q
        sudo -u xdmod xdmod-shredder -r nutsetters -d $REF_DIR/nutsetters -f openstack -q
        sudo -u xdmod xdmod-shredder -r openstack -d $REF_DIR/openstack_resource_specs -f cloudresourcespecs
        mysql -e "TRUNCATE TABLE modw_cloud.instance_type;"
        sudo -u xdmod xdmod-ingestor --last-modified-start-date="2017-01-01 00:00:00"
    fi

    if [[ "$XDMOD_REALMS" == *"storage"* ]];
    then
        for storage_dir in $REF_DIR/storage/*; do
            sudo -u xdmod xdmod-shredder -f storage -r $(basename $storage_dir) -d $storage_dir
        done
        last_modified_start_date=$(date +'%F %T')
        sudo -u xdmod xdmod-ingestor --datatype storage
        sudo -u xdmod xdmod-ingestor --aggregate=storage --last-modified-start-date "$last_modified_start_date"
    fi
fi

# Remove old PEAR dependencies.  This command can be removed when a new Docker
# image is created without these packages installed.
yum -y remove php-pear-MDB2 php-pear-MDB2-Driver-mysql
