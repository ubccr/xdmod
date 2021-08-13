FROM tas-tools-ext-01.ccr.xdmod.org/xdmod-base-9.5:centos7.9-1.0

ENV BRANCH=xdmod9.5
ENV REL=9.5.0
ENV BUILD=1.0
ENV TERM=xterm-256color
ENV XDMOD_TEST_MODE=fresh_install

# We have some caches that we put in place for automated builds.
# This will copy them into place if they exist
COPY assets /tmp/assets
RUN /tmp/assets/copy-caches.sh
COPY bin /root/bin

WORKDIR /root
RUN mkdir -p /root/rpmbuild/RPMS/noarch \
    && wget -nv -P /root/rpmbuild/RPMS/noarch https://github.com/ubccr/xdmod/releases/download/v$REL/xdmod-$REL-$BUILD.el7.noarch.rpm \
    && git clone --single-branch https://github.com/ubccr/xdmod/ --branch $BRANCH /root/xdmod \
    && /root/xdmod/tests/ci/bootstrap.sh \
    && ~/bin/services stop \
    && yum clean all \
    && rm -rf /var/cache/yum /root/xdmod /root/rpmbuild

WORKDIR /
