<?php namespace Models;

use CCR\DB;

/**
 * Class Acl
 *
 * Represents a named grouping under which a selection of 'Assets' can be
 * secured and to which a number of users can be belong. Data for this class
 * is stored in the 'acls' table while the relationship of user to acl is stored
 * in 'user_acls'.
 *
 * @package User
 *
 * The 'getters' and 'setters' for this class:
 * @method integer getAclId()
 * @method void    setAclId($aclId)
 * @method integer getModuleId()
 * @method void    setModuleId($moduleId)
 * @method integer getAclTypeId()
 * @method void    setAclTypeId($aclTypeId)
 * @method string  getName()
 * @method void    setName($name)
 * @method string  getDisplay()
 * @method void    setDisplay($display)
 * @method boolean getEnabled()
 * @method void    setEnabled($enabled)
 * @method integer getUserId()
 * @method void    setUserId($userId)
 * @method string  getOrganization()
 * @method void    setOrganization($organization)
 * @method integer getOrganizationId()
 * @method void    setOrganizationId($organizationId)
 */
class Acl extends DBObject
{
    protected $PROP_MAP = array(
        'acl_id' => 'aclId',
        'module_id' => 'moduleId',
        'acl_type_id' => 'aclTypeId',
        'name' => 'name',
        'display' => 'display',
        'enabled' => 'enabled',

        // Needed for getParameters
        'user_id' => 'userId',

        // Needed for getMostPrivilegedRole
        'organization' => 'organization',
        'organization_id' => 'organizationId'
    );
}
