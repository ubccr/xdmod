import path from 'path';
import fs from 'fs';

const artifacts = {
    testEnv: process.env.TEST_ENV !== undefined ? process.env.TEST_ENV : 'xdmod',
    artifactPath: path.join(__dirname, './../../../artifacts/'),
    getArtifact: function (name, type = 'output'){
        const filePath = path.join(this.artifactPath, this.testEnv, 'ui', type, name + '.json');
        return JSON.parse(fs.readFileSync(filePath));
    }
}

export default artifacts;
