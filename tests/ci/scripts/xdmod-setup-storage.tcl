#!/usr/bin/env expect
# Expect script that run s xdmod-setup to configure a freshly installed
# XDMoD instance. This script will fail if run against an already installed
# XDMoD.

#-------------------------------------------------------------------------------
# Configuration settings for the XDMoD resources

set resources [list]

# Storage Resources
lappend resources [list recex Recex tape cpu 2020-01-01 0 0]
lappend resources [list torx Torx stgrid cpu 2020-01-01 0 0]
# -----------------

#-------------------------------------------------------------------------------

# Load helper functions from helper-functions.tcl
source [file join [file dirname [info script]] helper-functions.tcl]

#-------------------------------------------------------------------------------
# main body - note there are some hardcoded addresses, usernames and passwords here
# they should typically not be changed as they need to match up with the
# settings in the docker container

set timeout 240
spawn "xdmod-setup"

# Enter config settings for each resource
selectMenuOption 4
foreach resource $resources {
	selectMenuOption 1
	provideInput {Resource Name:} [lindex $resource 0]
	provideInput {Formal Name:} [lindex $resource 1]
	provideInput {Resource Type*} [lindex $resource 2]
	provideInput {Resource Allocation Type*} [lindex $resource 3]
	provideInput {Resource Start Date, in YYYY-mm-dd format*} [lindex $resource 4]
	provideInput {How many CPU nodes does this resource have?} [lindex $resource 5]
	provideInput {How many total CPU processors (cpu cores) does this resource have?} [lindex $resource 6]
}

selectMenuOption s
confirmFileWrite yes
enterToContinue
confirmFileWrite yes
enterToContinue

selectMenuOption q

lassign [wait] pid spawnid os_error_flag value
exit $value
