const path = require('path');
const fs = require('fs');

module.exports = {
    testEnv: process.env.TEST_ENV !== undefined ? process.env.TEST_ENV : 'xdmod',
    artifactPath: path.join(__dirname, './../../artifacts/xdmod-test-artifacts/'),
    getArtifact: function (name, type = 'output') {
        var filePath = path.join(this.artifactPath, this.testEnv, 'automated_tests', type, name + '.json');
        return JSON.parse(fs.readFileSync(filePath));
    }
};
