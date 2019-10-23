FROM tas-tools-ext-01.ccr.xdmod.org/centos7.6:xdmod-base
ARG GIT_BRANCH=xdmod8.5
ARG GIT_USER=ubccr
# The ARG will get overwritten by --build-arg even though they are still "set" here
# if you want to test uncomment the run line below
# RUN echo $GIT_USER $GIT_BRANCH

ENV REL=8.5.0
ENV BUILD=1

ENV SRCDIR=/src/github.com/ubccr
ENV TERM=xterm-256color
ENV XDMOD_TEST_MODE=fresh_install

# We have some caches that we put in place for automated builds.
# This will copy them into place if they exist
COPY assets /tmp/assets
RUN /tmp/assets/copy-caches.sh
COPY bin /root/bin

RUN mkdir -p $SRCDIR \
    && git clone https://github.com/$GIT_USER/xdmod/ --branch $GIT_BRANCH --single-branch $SRCDIR/xdmod

# Install the XDMoD git repos locally so we can build the RPM from the requested branch
WORKDIR $SRCDIR/xdmod

RUN composer install \
    && open_xdmod/build_scripts/build_package.php -v --module xdmod \
    && mkdir -p /root/rpmbuild/SOURCES /root/rpmbuild/SPECS \
    && cp $SRCDIR/xdmod/open_xdmod/build/xdmod-$REL.tar.gz /root/rpmbuild/SOURCES \
    && tar -xOf /root/rpmbuild/SOURCES/xdmod-$REL.tar.gz xdmod-$REL/xdmod.spec > /root/rpmbuild/SPECS/xdmod.spec \
    && rpmbuild --quiet -bb /root/rpmbuild/SPECS/xdmod.spec \
    && tests/ci/bootstrap.sh \
    && ~/bin/services stop

WORKDIR /
RUN rm -rf ~/rpmbuild/* /tmp/assets $SRCDIR
