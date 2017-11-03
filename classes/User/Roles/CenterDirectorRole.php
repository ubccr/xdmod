<?php

namespace User\Roles;

use CCR\DB;

/*
 * 'Center Director' Role:
 *
 *      - Utilization data specific to the user's associated center/resource
 *      - Performance data specific to the user's associated center/resource
 *      - Ability to view detailed job-level information for users of the resource
 *
 */

class CenterDirectorRole extends \User\AuthenticatedRole
{

    public function __construct($role_id = ROLE_ID_CENTER_DIRECTOR)
    {
        parent::__construct($role_id);
    }//__construct

    // --------------------------------------------------

    public function getIdentifier($absolute_identifier = false)
    {
        if ($absolute_identifier == true)
            return ROLE_ID_CENTER_DIRECTOR.';'.$this->getActiveCenter();
        else
            return parent::getIdentifier($absolute_identifier);

    }//getIdentifier

    // --------------------------------------------------

    public function getFormalName()
    {
        $pdo = DB::factory('database');

        $baseName = parent::getFormalName();

        $active_center_id = $this->getActiveCenter();

        $center_data = $pdo->query("SELECT short_name description FROM modw.organization WHERE id=:id", array(
            ':id' => $active_center_id,
        ));

        $center_name = (count($center_data) > 0) ? $center_data[0]['description'] : '';

        return "$baseName - $center_name";

    }//getFormalName

    // --------------------------------------------------

    public function getActiveCenter()
    {
        if (!empty($this->_simulated_organization)) return $this->_simulated_organization;

        $pdo = DB::factory('database');

        $centerData = $pdo->query(
            "SELECT urp.param_value FROM moddb.UserRoleParameters AS urp, moddb.Roles AS r ".
                "WHERE urp.user_id=:user_id AND urp.is_active=1 AND r.role_id = urp.role_id AND r.abbrev=:role_id",
            array(
                'user_id' => $this->getCorrespondingUserID(),
                'role_id' => ROLE_ID_CENTER_DIRECTOR
            )
        );


        if (count($centerData) > 0)
            return $centerData[0]['param_value'];
        else
            return -1;

    }//getActiveCenter

    // --------------------------------------------------

    public function enumCenterStaffMembers()
    {

        $pdo = DB::factory('database');

        $organization_id = $this->getActiveCenter();

        // Locate users having a 'Center Staff' role and belong to the same (active) center as that
        // of the director.

        $query = "SELECT u.id, CONCAT(u.last_name, ', ', u.first_name) as name " .
                 "FROM Users AS u,  Roles AS r, UserRoleParameters AS urp " .
                 "WHERE u.id = urp.user_id AND r.role_id = urp.role_id AND r.abbrev='cs' " .
                 "AND urp.param_name = 'provider' AND urp.param_value=:param_value";

        $staffMembers = $pdo->query($query, array(
            ':param_value' => $organization_id,
        ));

        $centerStaffMembers = array();

        foreach($staffMembers as $member) {

            $centerStaffMembers[] = array('id' => $member['id'], 'name' => $member['name']);

        }//foreach

        return $centerStaffMembers;

    }//enumCenterStaffMembers

    // --------------------------------------------------

    public function upgradeStaffMember($member)
    {

        // Analyze the current 'role set' for the member, add the Center Director role
        // as necessary

        $roles = $member->getRoles();

        if (!in_array(ROLE_ID_CENTER_DIRECTOR, $roles)){
            $roles[] = ROLE_ID_CENTER_DIRECTOR;
        }

        $member->setRoles($roles);

        $pdo = DB::factory('database');

        $pdo->execute(
            "INSERT INTO UserRoleParameters(user_id, role_id, param_name, param_op, param_value, is_primary, is_active, promoter) VALUES " .
            "(:user_id, :role_id, :param_name, '=', :param_value, 0, 0, :promoter)",
            array(
                'user_id' => $member->getUserID(),
                'role_id' => \xd_roles\getRoleIDFromIdentifier(ROLE_ID_CENTER_DIRECTOR),
                'param_name' => 'provider',
                'param_value' => $this->getActiveCenter(),
                'promoter' => $this->getCorrespondingUserID()
            )
        );

        // Any exception thrown by invoking saveUser() will be handled by the caller of upgradeStaffMember()
        $member->saveUser();

    }//upgradeStaffMember

    // --------------------------------------------------

    public function downgradeStaffMember($member)
    {

        $pdo = DB::factory('database');

        $active_role_failover_needed = (
            ($member->getActiveRole()->getIdentifier() == ROLE_ID_CENTER_DIRECTOR) &&
            ($member->getActiveOrganization() == $this->getActiveCenter())
        );

        if ($active_role_failover_needed == true) {
            $member->assignActiveRoleToPrimary();
        }

        $pdo->execute(
            "DELETE FROM UserRoleParameters WHERE user_id=:user_id AND role_id=:role_id " .
                "AND param_name=:param_name AND param_value=:param_value ",
            array(
                'user_id' => $member->getUserID(),
                'role_id' => \xd_roles\getRoleIDFromIdentifier(ROLE_ID_CENTER_DIRECTOR),
                'param_name' => 'provider',
                'param_value' => $this->getActiveCenter()
            )
        );


        $center_director_affiliations = $member->getOrganizationCollection(ROLE_ID_CENTER_DIRECTOR);

        if (count($center_director_affiliations) == 0) {

            // Since this member no longer is a Center Director of any organization,
            // remove (revoke) the Center Director role from the member

            $updated_roles = array_diff($member->getRoles(), array(ROLE_ID_CENTER_DIRECTOR));

            $member->setRoles($updated_roles);

            // Any exception thrown by invoking saveUser() will be handled by the caller of upgradeStaffMember()
            $member->saveUser();

        }

    }//downgradeStaffMember

}//CenterDirectorRole

