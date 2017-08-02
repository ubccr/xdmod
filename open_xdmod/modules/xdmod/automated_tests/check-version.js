var semver = require('semver');

var requiredVersion = process.env.npm_package_engines_node;
var currentVersion = process.version;
if (!semver.satisfies(currentVersion, requiredVersion)) {
    process.stdout.write('Required node version ' + requiredVersion + ' not satisfied by current version ' + currentVersion + '\n');
    process.exit(-1);
}
