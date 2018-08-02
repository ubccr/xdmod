<?php

namespace User\Roles;

/*
 * 'Manager' Role:
 *
 *      - Ability to manage users (create, edit, disable, delete, etc)
 *
 */

class ManagerRole extends \User\AuthenticatedRole
{

    public function __construct()
    {
        parent::__construct(ROLE_ID_MANAGER);
    }//__construct

    public function configure(\XDUser $user, $simulatedActiveRole = NULL)
    {
        parent::configure($user, $simulatedActiveRole);
    }//configure

}//ManagerRole

?>
