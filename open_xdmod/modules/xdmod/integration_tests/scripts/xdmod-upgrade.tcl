#!/usr/bin/env expect
# Expect script that runs xdmod-upgrade to upgrade an already installed Open
# XDMoD instance.

#-------------------------------------------------------------------------------
# Helper functions

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
    -re "\nDo you want to run cloud aggregation now." {
        send yes\n
    }
}
expect {
    timeout {
        send_user "\nFailed to get prompt\n"; exit 1
    }
    -re "\nPlease enter the directory of your cloud logs to use for re-ingestion and aggregation of the Cloud realm." {
        send /var/tmp/referencedata/openstack\n
    }
}
expect {
    timeout {
        send_user "\nFailed to get prompt\n"; exit 1
    }
    -re "\nPlease enter the name of your cloud resource as shown in XDMOD." {
        send openstack\n
    }
}
expect {
    timeout {
        send_user "\nFailed to get prompt\n"; exit 1
    }
    -re "\nPlease specify the format of your cloud logs. If your cloud logs come from OpenStack, enter openstack; otherwise enter generic." {
        send openstack\n
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
