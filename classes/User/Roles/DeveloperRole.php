<?php

namespace User\Roles;

/*
 *
 */

class DeveloperRole extends \User\aRole
{
    public function __construct()
    {
        parent::__construct(ROLE_ID_DEVELOPER);

    }//__construct

}//PublicRole

?>
