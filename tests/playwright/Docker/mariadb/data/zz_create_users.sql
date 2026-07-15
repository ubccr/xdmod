# Create the xdmod user to be used during an upgrade.
CREATE USER 'xdmod'@'xdmod.docker_xdmod_default' IDENTIFIED BY 'xdmod123';

# Grant that user all privs on all dbs.
GRANT ALL PRIVILEGES ON *.* TO 'xdmod'@'xdmod.docker_xdmod_default';
FLUSH PRIVILEGES ;
