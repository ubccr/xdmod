@author: Amin Ghadersohi
@date: 2/4/2014

INSTALL

The etl scripts require node.js and npm are installed on the system. Tested
with node.js version 0.10.25. There are several node package dependencies.
Install the dependencies by running the command:

npm install

in the xdmod/etl/js directory.

NOTES:

Note 1: Please keep all supremm related functionality limited to the config/supremm/
folder. All files in the js folder related to 

File List:
config - config and functionality related to input and output data configs and mappings used in etl.
lib - collection of javascript files for etl from supremm data sources. To be used with node.js

etl.cli.js - main command-line etl script
etl.cluster.js - script to run multi-threaded etl

etl.js - the main etl script (called by etl.cli and etl.cluster)
config.js - the main config script

