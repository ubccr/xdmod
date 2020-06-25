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

# Begin 9.0 migration
provideInput {Re-ingest*} {yes}
provideInput {Re-ingest*} {yes}
# End 9.0 migration

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
