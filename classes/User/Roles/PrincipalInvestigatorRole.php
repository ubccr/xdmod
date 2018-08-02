<?php

namespace User\Roles;

use CCR\DB;

/*
 * 'Principal Investigator' Role:  (extends User role and adds the following data (requires an active XD account))
 *
 *      - List all users under each of the PI's allocations
 *      - Provide detailed utilization information for all users under a particular allocation (only view activity specific to the PI's allocations)
 *
 */

class PrincipalInvestigatorRole extends \User\AuthenticatedRole
{

    public function __construct()
    {
        parent::__construct(ROLE_ID_PRINCIPAL_INVESTIGATOR);

    }//__construct

    // -----------------------------------

    public function configure(\XDUser $user, $simulatedActiveRole = NULL)
    {
        parent::configure($user, $simulatedActiveRole);
    }//configure

}//PrincipalInvestigatorRole

?>
