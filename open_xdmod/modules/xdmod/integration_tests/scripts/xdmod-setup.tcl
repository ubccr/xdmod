#!/usr/bin/env expect
# Expect script that run s xdmod-setup to configure a freshly installed
# XDMoD instance. This script will fail if run against an already installed
# XDMoD.

#-------------------------------------------------------------------------------
# Configuration settings for the XDMoD resources
set resources [list]
lappend resources [list frearson Frearson 400 4000]
lappend resources [list mortorq Mortorq 400 4000]
lappend resources [list phillips Phillips 400 4000]
lappend resources [list pozidriv Posidriv 400 4000]
lappend resources [list robertson Robertson 400 4000]

#-------------------------------------------------------------------------------
# Helper functions

proc selectMenuOption { option } {
	
	expect {
		-re "\nSelect an option .*: "
	}
	send $option\n
}

proc answerQuestion { question response } {
	expect {
		timeout { send_user "\nFailed to get prompt\n"; exit 1 }
		-re "\n$question: \\\[.*\\\] "
	}
	send $response\n
}

proc provideInput { prompt response } {
	expect {
		timeout { send_user "\nFailed to get prompt\n"; exit 1 }
		"\n$prompt "
	}
	send $response\n
}

proc providePassword { prompt password } {
	provideInput $prompt $password
	provideInput "(confirm) $prompt" $password

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
# main body - note there are some hardcoded addresses, usernames and passwords here
# they should typically not be changed as they need to match up with the
# settings in the docker container

set timeout 10
spawn "xdmod-setup"

selectMenuOption 1
answerQuestion {Site Address} http://localhost:8080/
provideInput {Email Address:} ccr-xdmod-help@buffalo.edu
answerQuestion {Java Path} /usr/bin/java
answerQuestion {Javac Path} /usr/bin/javac
provideInput {PhantomJS Path:} /usr/local/bin/phantomjs
provideInput {Center Logo Path:} {}
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

selectMenuOption 4
foreach resource $resources {
	selectMenuOption 1
	provideInput {Resource Name:} [lindex $resource 0]	
	provideInput {Formal Name:} [lindex $resource 1]
	provideInput {How many nodes does this resource have?} [lindex $resource 2]
	provideInput {How many total processors (cpu cores) does this resource have?} [lindex $resource 3]
}
selectMenuOption s
confirmFileWrite yes
enterToContinue
confirmFileWrite yes
enterToContinue

selectMenuOption 5
provideInput {Username:} admin
providePassword {Password:} admin
provideInput {First name:} Admin
provideInput {Last name:} User
provideInput {Email address:} admin@localhost
enterToContinue

selectMenuOption 6
answerQuestion {Top Level Name} {Decanal Unit}
provideInput {Top Level Description:} {Decanal Unit}
answerQuestion {Middle Level Name} {Department}
provideInput {Middle Level Description:} {Department}
answerQuestion {Bottom Level Name} {PI Group}
provideInput {Bottom Level Description:} {PI Group}
confirmFileWrite yes
enterToContinue

selectMenuOption q
