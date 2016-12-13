/*node.js javascript document
 *
 * @authors: Amin Ghadersohi
 * @date: 2/6/2014
 *
 * Holds config information for the etl process. 
 * supports multiple etl data schemas profiles
 *
 * @requirements:
 *	node.js
 *
 */

var fs = require('fs');
var ini = require('ini');
var path = require('path');
var extend = require('util')._extend;

var xdmodRoot = fs.realpathSync(__dirname + '/../..');

/** return the directory path that contains the requested file
 *  or an empty string
 */
var findFilePath = function(searchpaths, filename) {
    var i, len = searchpaths.length;
    for(i = 0; i < len; i++) {
        try {
            fs.statSync(searchpaths[i] + '/' + filename);
            return searchpaths[i];
        } catch(ignore) {
            // pass
        }
    }
    return '';
};

/* Configuration directory location is different between open source version
 * and XSEDE version. Try to detect which.  */
var searchpaths = [xdmodRoot + '/configuration', xdmodRoot + '/../etc', '/etc/xdmod'];

var xdmodConfigDir = findFilePath(searchpaths, 'portal_settings.ini');

if(xdmodConfigDir === '') {
    console.error("Error unable to determine path to XDMOD configuration directory.");
    process.exit(1);
}

/**
 * Read contents of XDMoD-style ini configuration files. The configuration file
 * structure is a top level ini file and a .d directory containing ini files
 * that may extend or override settings in the original file. Files in the .d directory
 * are processed in alphabetical order.
 */
var recursiveIniParser = function(basePath, baseName) {

    var config, filelist, subconfig, i, len;

    config = ini.parse(fs.readFileSync(basePath + "/" + baseName + ".ini", "utf-8"));

    try {
        filelist = fs.readdirSync(basePath + "/" + baseName + ".d");
    } catch(err) {

        if(err.code === "ENOENT") {
            // Ok for no .d directory to exist
            return config;
        }
        throw err;
    }

    filelist.sort();

    len = filelist.length;
    for(i = 0; i < len; i++) {
        if(filelist[i].indexOf(".ini", filelist[i].length - 4) !== -1) {
            subconfig = ini.parse(fs.readFileSync(basePath + "/" + baseName + ".d/" + filelist[i], "utf-8"));
            config = extend(config, subconfig);
        }
    }

    return config;
};

// Load the main XDMoD configuration file.
var xdmodConfig = recursiveIniParser(xdmodConfigDir, "portal_settings");
var loggerConfigSection = xdmodConfig.logger;

module.exports = {
	profiles: [
		require( './config/supremm/etl.profile.js' )
		//other profiles can be added here
	],
	etlLogging: {
		dbEngine: 'mysqldb', 
		config: {
			database: 'modw_etl',
			host: loggerConfigSection.host,
			port: loggerConfigSection.port,
			user: loggerConfigSection.user,
			password: loggerConfigSection.pass
		}
	},
	xdmodRoot: xdmodRoot,
	xdmodConfigDir: xdmodConfigDir,
	xdmodConfig: xdmodConfig,

    getXdmodConfigFile: function(filename) {
        var confFile = xdmodConfigDir + '/' + filename + '.json';
        try {
            return JSON.parse(fs.readFileSync(confFile));
        }
        catch(err) {
            if(err.code === "ENOENT") {
                console.error("ERROR: unable to find configuration file \"" + filename + ".json\" in the\n" +
                              "XDMoD configuration directory \"" + xdmodConfigDir + "\".\n" +
                              "This file should be created using the instructions in the install guide.");
                process.exit(1);
            }
            else {
                throw err;
            }
        }

    },

    // A basic mechanism to get at the configuration settings in any
    // json or ini config file in the XDMoD config directory.
    //
    // Adding support for the *.d directories
    // and xpath expressions is left as an exercise for the reader.
    // 
    parseuri: function(configuri) {

        if( configuri.indexOf("config://") !== 0 ) {
            // Pass through non-config uris without modification
            return configuri;
        }

        var items = configuri.substring(9).split(":");

        if(items.length !== 2 ) {
            throw new Error("unsupported config uri syntax");
        }

        var filename = items[0];
        var data;

        switch(path.extname(filename)) {
            case ".json":
                data = JSON.parse(fs.readFileSync(xdmodConfigDir + "/" + filename));
                break;
            case ".ini":
                data = recursiveIniParser(xdmodConfigDir, filename.substr(0, filename.length - 4));
                break;
            default:
                throw new Error("unsupported config file format");
        }

        return data[ items[1] ];
    }

};
