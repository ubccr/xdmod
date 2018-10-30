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
            "SELECT u.organization_id FROM Users u WHERE u.id = :user_id",
            array(
                ':user_id' => $this->getCorrespondingUserID()
            )
        );


        if (count($centerData) > 0)
            return $centerData[0]['organization_id'];
        else
            return -1;

    } //getActiveCenter
} //CenterDirectorRole

