<?php
/**
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace OpenXdmod\Setup;

use Models\Services\Organizations;
use XDUser;

/**
 * Admin user setup.
 */
class AdminUserSetup extends SetupItem
{

    /**
     * @inheritdoc
     */
    public function handle()
    {
        $this->console->displaySectionHeader('Create Admin User');

        $username     = $this->console->prompt('Username:');
        $password     = $this->console->silentPrompt('Password:');
        $firstName    = $this->console->prompt('First name:');
        $lastName     = $this->console->prompt('Last name:');
        $emailAddress = $this->console->prompt('Email address:');

        try {
            $user = new XDUser(
                $username,
                $password,
                $emailAddress,
                $firstName,

                // Middle name.
                '',

                $lastName,
                array(ROLE_ID_MANAGER, ROLE_ID_USER),
                ROLE_ID_MANAGER,
                -1,
                -1
            );

            // Internal user.
            $user->setUserType(2);

            $user->saveUser();
        } catch (\Exception $e) {
            $this->console->displayBlankLine();
            $this->console->displayMessage('Failed to create admin user:');

            do {
                $this->console->displayBlankLine();
                $this->console->displayMessage($e->getMessage());
                $this->console->displayBlankLine();
                $this->console->displayMessage($e->getTraceAsString());
            } while ($e = $e->getPrevious());

            $this->console->displayBlankLine();
            $this->console->prompt('Press ENTER to continue.');
            return;
        }

        $this->console->displayBlankLine();
        $this->console->displayMessage('Admin user created.');
        $this->console->displayBlankLine();
        $this->console->prompt('Press ENTER to continue.');
    }
}
