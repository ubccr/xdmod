FROM tas-tools-ext-01.ccr.xdmod.org/centos7.6:xdmod-base
ARG GIT_BRANCH=xdmod8.1
ARG GIT_USER=ubccr
# The ARG will get overwritten by --build-arg even though they are still "set" here
# if you want to test uncomment the run line below
# RUN echo $GIT_USER $GIT_BRANCH

ENV REL=8.1.2
ENV BUILD=1

ENV SRCDIR=/src/github.com/ubccr
ENV TERM=xterm-256color
ENV XDMOD_TEST_MODE=fresh_install

# We have some caches that we put in place for automated builds.
# This will copy them into place if they exist
COPY assets /tmp/assets
RUN bash /tmp/assets/copy-caches.sh
COPY bin /root/bin

RUN mkdir -p $SRCDIR && \
    git clone https://github.com/$GIT_USER/xdmod/ --branch $GIT_BRANCH --single-branch $SRCDIR/xdmod

# Install the XDMoD git repos locally so we can build the RPM from the requested branch
WORKDIR $SRCDIR/xdmod

RUN composer install
RUN open_xdmod/build_scripts/build_package.php -v --module xdmod
RUN mkdir -p /root/rpmbuild/SOURCES /root/rpmbuild/SPECS && \
    cp $SRCDIR/xdmod/open_xdmod/build/xdmod-$REL.tar.gz /root/rpmbuild/SOURCES && \
    tar -xOf /root/rpmbuild/SOURCES/xdmod-$REL.tar.gz xdmod-$REL/xdmod.spec > /root/rpmbuild/SPECS/xdmod.spec && \
    rpmbuild --quiet -bb /root/rpmbuild/SPECS/xdmod.spec
RUN open_xdmod/modules/xdmod/integration_tests/scripts/bootstrap.sh && ~/bin/services stop

WORKDIR /
RUN rm -rf ~/rpmbuild/*
RUN rm -rf /tmp/assets
RUN rm -rf $SRCDIR
