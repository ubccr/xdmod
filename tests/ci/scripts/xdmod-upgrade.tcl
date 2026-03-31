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

expect {
    timeout {
        send_user "\nFailed to get prompt\n"; exit 1
    }
    -re "\nWould you like to proceed.*\\\] "  {
        send yes\n
    }
}

expect {
    timeout {
        send_user "\nFailed to get prompt\n"; exit 1
    }
    -re "\nMySQL Admin Username: \\\[.*\\\] " {
        send root\n
    }
}

expect {
    timeout {
        send_user "\nFailed to get prompt\n"; exit 1
    }
    -re "\nMySQL Admin Password: " {
        send \n
    }
}

expect {
    timeout {
        send_user "\nFailed to get prompt\n"; exit 1
    }
    -re "\n\\(confirm\\) MySQL Admin Password: " {
        send \n
    }
}

expect {
    timeout {
        send_user "\nFailed to get prompt\n"; exit 1
    }
    "Upgrade Complete" {
        lassign [wait] pid spawnid os_error_flag value
    }
}

exit $value
