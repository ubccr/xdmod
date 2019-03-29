#!/usr/bin/env expect
# Expect script that runs xdmod-setup to add storage resources to an already
# installed Open XDMoD instance.

#-------------------------------------------------------------------------------
# Configuration settings for the XDMoD resources
set resources [list]
lappend resources [list recex Recex tape 0 0]
lappend resources [list torx Torx stgrid 0 0]

# Load helper functions from helper-functions.tcl
source [file join [file dirname [info script]] helper-functions.tcl]

#-------------------------------------------------------------------------------
# main body

set timeout 240
spawn "xdmod-setup"

selectMenuOption 4
foreach resource $resources {
	selectMenuOption 1
	provideInput {Resource Name:} [lindex $resource 0]
	provideInput {Formal Name:} [lindex $resource 1]
	provideInput {Resource Type*} [lindex $resource 2]
	provideInput {How many nodes does this resource have?} [lindex $resource 3]
	provideInput {How many total processors (cpu cores) does this resource have?} [lindex $resource 4]
}

selectMenuOption s
confirmFileWrite yes
enterToContinue
confirmFileWrite yes
enterToContinue
selectMenuOption q

lassign [wait] pid spawnid os_error_flag value
exit $value
