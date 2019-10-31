# XDMoD Docker

The XDMoD Team currently uses Docker for development, testing, and demonstrations.

The file `tests/ci/Docker/Dockerfile` in the `ubccr/xdmod` GitHub repository is used to build the Docker image used for testing.

Start Docker with the following command:

**NOTE:** Make changes to `-v`, `-p`, `--env-file` as appropriate.

```bash
docker run --rm -h xdmod9_0 --shm-size 2g -it -v ~/scratch:/scratch -p 3306:3306 -p 8080:8080 --env-file ~/xdmod.env tas-tools-ext-01.ccr.xdmod.org/centos7_6-open8.5.1:latest /bin/bash
```

## Demonstration

Services (database, web server, etc.) are not running in the Docker image and
must be started manually:

```bash
~/bin/services start
```

## Development

To assist with development when using Docker you can enable `xdebug` by running
the following script inside of the Docker container.  This must be done before
starting Apache.

```bash
#!/bin/bash

rpm -qa | grep php-pecl-xdebug > /dev/null 2>&1
if [ $? != 0 ]; then
    yum install php-pecl-xdebug -y
fi

grep 'xdebug.remote_host' /etc/php.d/xdebug.ini > /dev/null 2>&1
if [ $? != 0 ]; then

    DOCKER_HOST=""

    # read each input line capturing dest, gw and mask as the main interesting bits
    while read -r _ dest gw _ _ _ _ mask _; do
        # if both dest and mask are all 0s
        if [ "$dest" == "00000000" ] && [ "$mask" == "00000000" ]; then
            # reformat the gateway as a dotted quad ip
            DOCKER_HOST=`printf "%d.%d.%d.%d" "0x${gw:6:2}" "0x${gw:4:2}" "0x${gw:2:2}" "0x${gw:0:2}"`
            # don't process more of the loop (probably optional)
            break
        fi
    done < /proc/net/route

    cat <<EOT >> /etc/php.d/xdebug.ini
xdebug.remote_enable=1
xdebug.remote_autostart=1
xdebug.remote_host=$DOCKER_HOST
EOT
fi
```

After that use the editor of your choice on your host machine with port 9000 to debug.

When developing new features a build script is commonly used to pull in our
code and make changes.

Below is an example:

```bash
#!/bin/bash
XDMOD_GIT_USER=${XDMOD_GIT_USER:-'ubccr'}
XDMOD_GIT_BRANCH=${XDMOD_GIT_BRANCH:-'xdmod9.0'}
#upgrade if you dont want to reingest data and are not testing that portion
#otherwise fresh_install
XDMOD_TEST_MODE=${XDMOD_TEST_MODE:-'upgrade'}

SRCDIR=/root/src/github.com/ubccr

mkdir -p $SRCDIR

git clone --single-branch https://github.com/$XDMOD_GIT_USER/xdmod/ --branch $XDMOD_GIT_BRANCH $SRCDIR/xdmod

cd $SRCDIR/xdmod
echo "Running composer install"
composer install -q
echo "Composer install finished"

BUILD_DIR=$SRCDIR/xdmod/open_xdmod/build
SCRIPT_DIR=$SRCDIR/xdmod/open_xdmod/build_scripts
mkdir -p ~/rpmbuild/{BUILD,RPMS,SOURCES,SPECS,SRPMS}
echo '%_topdir %(echo $HOME)/rpmbuild' > ~/.rpmmacros

rm -rf $BUILD_DIR/*.tar.gz

$SCRIPT_DIR/build_package.php --module xdmod

for file in $BUILD_DIR/*.tar.gz
do
    rpmfile=$(basename $file)
    rpmname=$(basename $rpmfile .tar.gz)
    pkgname=$(echo $rpmname | egrep -o '^[a-z,-]*' | sed 's/-$//')

    cp $file $HOME/rpmbuild/SOURCES
    cd $HOME/rpmbuild/SPECS
    tar xOf $HOME/rpmbuild/SOURCES/$rpmfile $rpmname/$pkgname.spec > $pkgname.spec
    rpmbuild -bb $pkgname.spec
done
$SRCDIR/xdmod/tests/ci/bootstrap.sh

sed -i -- 's/value: this.dateRanges\[this.defaultCannedDateIndex\].start,$/value: new Date(2016, 11, 22),/' /usr/share/xdmod/html/gui/js/DurationToolbar.js
sed -i -- 's/value: this.dateRanges\[this.defaultCannedDateIndex\].end,$/value: new Date(2018, 7, 2),/' /usr/share/xdmod/html/gui/js/DurationToolbar.js
sed -i -- 's/this.defaultCannedDate = this.dateRanges\[this.defaultCannedDateIndex\].text;/this.defaultCannedDate = "User Defined";/' /usr/share/xdmod/html/gui/js/DurationToolbar.js

cp /etc/xdmod/portal_settings.ini $SRCDIR/xdmod/configuration
```

## Testing

See the `tests` folder for more information.
