#!/usr/bin/env expect
# Expect script that runs xdmod-upgrade to upgrade an already installed Open
# XDMoD instance.

#-------------------------------------------------------------------------------

# Helper functions
proc provideInput { prompt response } {
	expect {
		timeout { send_user "\nFailed to get prompt\n"; exit 1 }
		"\n$prompt "
	}
	send $response\n
}

proc selectMenuOption { option } {

	expect {
		-re "\nSelect an option .*: "
	}
	send $option\n
}

proc enterToContinue { } {
	expect {
		timeout { send_user "\nFailed to get prompt\n"; exit 1 }
		"\nPress ENTER to continue. "
	}
	send \n
}

proc confirmFileWrite { response } {
	expect {
		timeout { send_user "\nFailed to get prompt\n"; exit 1 }
		-re "\nOverwrite config file .*\\\[.*\\\] "
	}
	send $response\n
}

#-------------------------------------------------------------------------------

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
