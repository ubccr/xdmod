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

    public function configure(\XDUser $user, $simulatedActiveRole = null)
    {
        parent::configure($user, $simulatedActiveRole);

        // $p = new \DataWarehouse\Query\Model\Parameter('person_id', '=', $user->getPersonID());

        $this->addParameter('person', $user->getPersonID());
    }//configure
}//ManagerRole
