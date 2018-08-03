<?php

namespace User\Roles;

/*
 * 'User' Role:  (extends Public role and adds the following data (requires an active XD account))
 *
 *      - Personal utilization information for the authenticated user
 *      - Drill down to user's individual job details and allocation information
 *
 */

class UserRole extends \User\AuthenticatedRole
{
    public function __construct()
    {
        parent::__construct(ROLE_ID_USER);

    }//__construct

    // -----------------------------------

    public function configure(\XDUser $user, $simulatedActiveRole = NULL)
    {
        parent::configure($user, $simulatedActiveRole);
    }//configure

}//UserRole

?>
