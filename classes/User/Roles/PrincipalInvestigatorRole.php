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

        $dbh = DB::factory('datawarehouse');

        $pi_check_query = $dbh->query("SELECT person_id from modw.principalinvestigator WHERE person_id=:person_id LIMIT 1", array(
            ':person_id' => $user->getPersonID(),
        ));

        $pi_mapping = (count($pi_check_query) == 1) ? $user->getPersonID() : -1;

        $this->addParameter('pi',  $pi_mapping);

    }//configure

}//PrincipalInvestigatorRole

?>
