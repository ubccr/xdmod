#!/usr/bin/env expect
# Expect script that run s xdmod-setup to configure a freshly installed
# XDMoD instance. This script will fail if run against an already installed
# XDMoD.

#-------------------------------------------------------------------------------
# Configuration settings for the XDMoD resources
set resources [list]
lappend resources [list frearson Frearson 400 4000]
lappend resources [list mortorq Mortorq 400 4000]
lappend resources [list phillips Phillips 400 4000]
lappend resources [list pozidriv Posidriv 400 4000]
lappend resources [list robertson Robertson 400 4000]

# Load helper functions from helper-functions.tcl
source [file join [file dirname [info script]] helper-functions.tcl]

#-------------------------------------------------------------------------------
# main body - note there are some hardcoded addresses, usernames and passwords here
# they should typically not be changed as they need to match up with the
# settings in the docker container

set timeout 240
spawn "xdmod-setup"

selectMenuOption 1
answerQuestion {Site Address} http://localhost:8080/
provideInput {Email Address:} ccr-xdmod-help@buffalo.edu
answerQuestion {Java Path} /usr/bin/java
answerQuestion {Javac Path} /usr/bin/javac
provideInput {PhantomJS Path:} /usr/local/bin/phantomjs
provideInput {Center Logo Path:} {}
confirmFileWrite yes
enterToContinue

selectMenuOption 2
answerQuestion {DB Hostname or IP} localhost
answerQuestion {DB Port} 3306
answerQuestion {DB Username} xdmod
providePassword {DB Password:} xdmod123
answerQuestion {DB Admin Username} root
providePassword {DB Admin Password:} {}
confirmFileWrite yes
enterToContinue
provideInput {Do you want to see the output*} {no}

selectMenuOption 3
provideInput {Organization Name:} Screwdriver
provideInput {Organization Abbreviation:} screw
confirmFileWrite yes
enterToContinue

selectMenuOption 4
foreach resource $resources {
	selectMenuOption 1
	provideInput {Resource Name:} [lindex $resource 0]
	provideInput {Formal Name:} [lindex $resource 1]
	provideInput {Resource Type*} {}
	provideInput {How many nodes does this resource have?} [lindex $resource 2]
	provideInput {How many total processors (cpu cores) does this resource have?} [lindex $resource 3]
}

selectMenuOption 1
provideInput {Resource Name:} openstack
provideInput {Formal Name:} OpenStack
provideInput {Resource Type*} cloud
provideInput {How many nodes does this resource have?} 123
provideInput {How many total processors (cpu cores) does this resource have?} 234

selectMenuOption s
confirmFileWrite yes
enterToContinue
confirmFileWrite yes
enterToContinue

selectMenuOption 5
provideInput {Username:} admin
providePassword {Password:} admin
provideInput {First name:} Admin
provideInput {Last name:} User
provideInput {Email address:} admin@localhost
enterToContinue

selectMenuOption 6
answerQuestion {Top Level Name} {Decanal Unit}
provideInput {Top Level Description:} {Decanal Unit}
answerQuestion {Middle Level Name} {Department}
provideInput {Middle Level Description:} {Department}
answerQuestion {Bottom Level Name} {PI Group}
provideInput {Bottom Level Description:} {PI Group}
confirmFileWrite yes
enterToContinue

selectMenuOption q

lassign [wait] pid spawnid os_error_flag value
exit $value
