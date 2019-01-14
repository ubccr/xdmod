<?php

namespace User\Roles;

use CCR\DB;

/*
 * 'Center Staff' Role:
 *
 *
 *
 */

class CenterStaffRole extends CenterDirectorRole
{

    public function __construct()
    {
        parent::__construct(ROLE_ID_CENTER_STAFF);
    }//__construct

    // -----------------------------------------------------------

    public function getIdentifier($absolute_identifier = false)
    {
        if ($absolute_identifier == true)
            return ROLE_ID_CENTER_STAFF.';'.$this->getActiveCenter();
        else
            return parent::getIdentifier($absolute_identifier);

    }//getIdentifier

    // -----------------------------------------------------------

    // configure: Generates the parameters associated with the parent user the role mapped to that user.

    public function configure(\XDUser $user, $simulatedActiveRole = NULL)
    {
        parent::configure($user, $simulatedActiveRole);
    }//configure

    // -----------------------------------------------------------

    public function getActiveCenter()
    {
        if (!empty($this->_simulated_organization)) return $this->_simulated_organization;

        $pdo = DB::factory('database');

        $centerData = $pdo->query(
            "SELECT u.organization_id FROM Users u WHERE u.id = :user_id",
            array(
                'user_id' => $this->getCorrespondingUserID()
            )
        );

        if (count($centerData) > 0)
            return $centerData[0]['organization_id'];
        else
            return -1;

    }//getActiveCenter

}//CenterStaffRole

?>
