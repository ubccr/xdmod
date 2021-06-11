#!/usr/bin/env expect
# Expect script that run s xdmod-setup to configure a freshly installed
# XDMoD instance. This script will fail if run against an already installed
# XDMoD.

# Load helper functions from helper-functions.tcl
source [file join [file dirname [info script]] helper-functions.tcl]

#-------------------------------------------------------------------------------
# main body - note there are some hardcoded addresses, usernames and passwords here
# they should typically not be changed as they need to match up with the
# settings in the docker container

set timeout 240
spawn "xdmod-setup"

selectMenuOption 1
answerQuestion {Site Address} https://localhost/
provideInput {Email Address:} xdmod@example.com
answerQuestion {Java Path} /usr/bin/java
provideInput {Javac Path:} /usr/bin/javac
provideInput {Chromium Path:} /usr/lib64/chromium-browser/headless_shell
provideInput {Center Logo Path:} {}
provideInput {Enable Dashboard Tab*} {off}
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

selectMenuOption 3
provideInput {Organization Name:} Screwdriver
provideInput {Organization Abbreviation:} screw
confirmFileWrite yes
enterToContinue

selectMenuOption q

lassign [wait] pid spawnid os_error_flag value
exit $value
