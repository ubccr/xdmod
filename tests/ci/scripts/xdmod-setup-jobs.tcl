#!/usr/bin/env expect
# Expect script that run s xdmod-setup to configure a freshly installed
# XDMoD instance. This script will fail if run against an already installed
# XDMoD.

#-------------------------------------------------------------------------------
# Configuration settings for the XDMoD resources

set resources [list]

# Job Resources
lappend resources [list frearson Frearson hpc cpu 2016-12-27 400 4000]
lappend resources [list mortorq Mortorq hpc gpu 2016-12-26 400 4000 400 4000]
lappend resources [list phillips Phillips hpc cpunode 2016-12-22 400 4000]
lappend resources [list pozidriv Posidriv hpc cpu 2016-12-21 400 4000]
lappend resources [list robertson Robertson hpc gpunode 2016-12-12 400 4000 400 4000]
# -------------

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
	if { [lindex $resource 3] == "gpu" || [lindex $resource 3] == "gpunode" } {
		provideInput {How many GPU nodes does this resource have?} [lindex $resource 7]
		provideInput {How many total GPUs does this resource have?} [lindex $resource 8]
	}
}

selectMenuOption s
confirmFileWrite yes
enterToContinue
confirmFileWrite yes
enterToContinue

selectMenuOption q

lassign [wait] pid spawnid os_error_flag value
exit $value
