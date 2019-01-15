#!/usr/bin/env expect
# Expect script that runs xdmod-upgrade to upgrade an already installed Open
# XDMoD instance.

#-------------------------------------------------------------------------------
# Helper functions

source [file join [file dirname [info script]] helper-functions.tcl]

proc confirmUpgrade { } {
    expect {
        timeout { send_user "\nFailed to get prompt\n"; exit 1 }
        -re "\nAre you sure you want to continue .*\\\] "
    }
    send yes\n
}

#-------------------------------------------------------------------------------
# main body

set timeout 180
spawn "xdmod-upgrade"
confirmUpgrade
provideInput {Enable Novice User Tab*} {off}
expect {
    -re "\nDo you want to run aggregation now.*\\\]" {
        send yes\n
    }
    timeout {
        send_user "\nFailed to get prompt\n"; exit 1
    }
}
expect {
    timeout {
        send_user "\nFailed to get prompt\n"; exit 1
    }
    "\nPress ENTER to continue." {
        send \n
        exp_continue
    }
    "Upgrade Complete" {
        lassign [wait] pid spawnid os_error_flag value
    }
}

exit $value
