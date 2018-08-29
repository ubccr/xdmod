#!/usr/bin/env expect
# Expect script that runs xdmod-upgrade to upgrade an already installed Open
# XDMoD instance.

# Load helper functions from helper-functions.tcl
source [file join [file dirname [info script]] helper-functions.tcl]

set timeout 10
spawn "xdmod-setup"

selectMenuOption 4
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
selectMenuOption q

lassign [wait] pid spawnid os_error_flag value
exit $value
