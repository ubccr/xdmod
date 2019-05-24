Some test require a valid user account on XDMoD. The username and password
are read from a file called testing.json in this directory.
This file has hard coded user names and passwords that are get created as part
of the CI build process.

## Building From A Base Image

To speed up testing we can create a base image which includes centos with updates, composer, and
phantomjs and then build our XDMoD image on top of that.

```bash
docker build -t centos7:xdmod-base -f Dockerfile.centos7:xdmod-base .
```

## Cache and Config Files
We use some default configuration and cache files to set some defaults for
development and speed up the build times of Docker.
If you look in `assets/copy-caches.sh` you will see all of the files that will be put into place.

### vimrc
This is just some defaults for VIM when using the docker for development

### npmrc
This is a default used for installing the chromedriver instead of downloading it
as part of an npm install when using the UI tests.  It also turns off the npm
progress bar

### browser-tests-node-modules.tar.gz
the node_modules folder for the UI tests

### saml-idp.tar.gz
the node_modules for the saml-idp server used for testing

### mysql-server.cnf
defaults for mysql server

### composer-cache.tar.gz
The composer cache to speed up the install of composer modules.

### chromedriver_linux64.zip
chromedriver for automated tests. Since google now versions with chrome the
chromedriver node module doesn't currently handle the installed version.
This also prevents the need to download it every time.

https://sites.google.com/a/chromium.org/chromedriver/downloads

### PhantomJS
Since PhantomJS is no longer maintained, we have a copy that we use instead of
automatically getting it from the internet every time
https://bitbucket.org/ariya/phantomjs/downloads/phantomjs-2.1.1-linux-x86_64.tar.bz2
