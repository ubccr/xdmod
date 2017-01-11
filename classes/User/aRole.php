<?php

namespace User;

use CCR\DB;
use Xdmod\Config;

/**
 * Abstract "factory" class for mapping role-based information to a
 * particular user.  The XDUser class will rely on this class for
 * ultimately determining what data is available to any given user.
 *
 * @author: Amin Ghadersohi
 */
abstract class aRole implements \User\iRole
{

    /**
     * Assigned in call to constructor of target role extending this
     * class.
     *
     * @var string
     */
    private $_identifier;

    /**
     * The id of the user this role belongs to.
     *
     * @var int
     */
    private $_user_id;

    /**
     * @var string
     */
    protected $_simulated_organization = '';

    /**
     * Role parameters.
     *
     * @var array
     */
    private $_params = array();

    /**
     * The modules this role is permitted to access.
     *
     * @var array
     */
    private $_permittedModules = array();

    /**
     * Maps querygroup_name to Query object.
     *
     * This is a deaply nested array containing the query descripters.
     * The first key is the query groupname, then the realm name, then
     * the group by name, and lastly the statistic name or "all"
     *
     * e.g.:
     * $qd = $this->_querys[$query_groupname][$realm_name][$group_by_name]['all'];
     *
     * @var array
     */
    private $_querys = array();

    /**
     * @var array
     */
    protected $_roleCategories = array();

    /**
     * Role configuration from roles.json.
     *
     * @var array
     */
    private static $_config;

    /**
     * Create an instance of the specified role.
     *
     * @param string $role The role identifier.
     *
     * @return aRole A concrete subclass instance of abstract aRole.
     */
    public static function factory($role)
    {
        if (!(isset($role))) {
            throw new \Exception("A role identifier must be specified");
        }

        $role_class
            = '\\User\\Roles\\'
            . str_replace(' ', '', $role)
            . 'Role';

        $role_definition_file
            = __DIR__
            . '/Roles/'
            . str_replace(' ', '', $role)
            . 'Role'
            . '.php';

        if (!file_exists($role_definition_file)) {
            throw new \Exception("Role class file not found for '$role'");
        }

        require_once $role_definition_file;

        // Ensure that the class has been loaded.
        if (!class_exists($role_class)) {
            throw new \Exception("Role '$role_class' not loaded for '$role'");
        }

        // This call will invoke the role's constructor, ultimately
        // assigning $this->_identifier.
        return new $role_class();
    }

    /**
     * Returns the data stored in the roles.json config file.
     *
     * @return array Roles config data.
     */
    protected static function getConfigData()
    {
        if (!isset(self::$_config)) {
            $config = Config::factory();
            self::$_config = $config['roles']['roles'];
        }

        return self::$_config;
    }

    /**
     * Returns the config for the specified role identifier.
     *
     * @param string $identifier Role identifier.
     * @param string $section Optional section from the role config that
     *     should be returned.
     *
     * @return array
     */
    protected static function getConfig($identifier, $section = null)
    {
        foreach (self::getConfigData() as $key => $data) {
            if ($key == $identifier) {
                if ($section === null) {
                    return $data;
                } elseif (array_key_exists($section, $data)) {
                    return $data[$section];
                } else {
                    if ($identifier == 'default') {
                        $msg = "No data found for section '$section'";
                        throw new \Exception($msg);
                    }

                    if (array_key_exists('extends', $data)) {
                        return self::getConfig($data['extends'], $section);
                    }

                    return self::getConfig('default', $section);
                }
            }
        }

        throw new \Exception("Unknown role '$identifier'");
    }

    /**
     * All classes which extend aRole will make a call to this
     * constructor, passing in an identifier (all of which are defined
     * in configuration/constants.php)
     *
     * @param string $identifier
     */
    protected function __construct($identifier)
    {

        $this->_identifier = $identifier;

        // This variable will be assigned using the call to "configure"
        $this->_user_id = null;

        $this->_roleCategories = array('tg' => ORGANIZATION_NAME);

        $modules = self::getConfig($this->_identifier, 'permitted_modules');

        foreach ($modules as $moduleConfig) {
            $this->addPermittedModule(
                new \User\Elements\Module($moduleConfig)
            );
        }

        $descripters = self::getConfig(
            $this->_identifier,
            'query_descripters'
        );

        $querydescriptors = array();

        foreach ($descripters as $descripterConfig) {
            $descripter = new \User\Elements\QueryDescripter(
                'tg_usage',
                $descripterConfig['realm'],
                $descripterConfig['group_by']
            );

            if (isset($descripterConfig['show'])) {
                $descripter->setShowMenu($descripterConfig['show']);
            }

            if (isset($descripterConfig['disable'])) {
                $descripter->setDisableMenu($descripterConfig['disable']);
            }

            if ($descripter->getGroupByName() == "none") {
                // Special case: the group by none class always go at the top of the list.
                $this->addQueryDescripter($descripter);
            } else {
                $querydescriptors[ $descripterConfig['realm'] . "." . $descripter->getGroupByLabel()] = $descripter;
            }
        }

        uksort($querydescriptors, 'strcasecmp');
        foreach ($querydescriptors as $querydescriptor) {
            $this->addQueryDescripter($querydescriptor);
        }
    }

    /**
     * Generates the parameters associated with a user and the role
     * mapped to that user.  Access to the parameters is accomplished by
     * calling getParameters()
     *
     * @param XDUser $user
     * @param int $simulatedActiveRole (optional) if supplied,
     *    "configure"(...) will not check for an active flag; instead
     *    it will directly consult the role referenced by
     *    $simulatedActiveRole)
     * @param bool $simulatedActiveRole
     */
    public function configure(\XDUser $user, $simulatedActiveRole = null)
    {
        $this->_params = array();

        $this->_user_id = $user->getUserID();

        if ($simulatedActiveRole != null) {
            $this->_simulated_organization = $simulatedActiveRole;

            $query = "
                SELECT
                    p.param_name,
                    p.param_op,
                    p.param_value
                FROM UserRoleParameters AS p,
                    Roles AS r
                WHERE user_id = :user_id
                    AND r.abbrev = :abbrev
                    AND r.role_id = p.role_id
                    AND p.param_value = :param_value
            ";
            $query_params = array(
                ':user_id' => $user->getUserID(),
                ':abbrev' => $this->getIdentifier(),
                ':param_value' => $simulatedActiveRole,
            );
        } else {
            $query = "
                SELECT
                    p.param_name,
                    p.param_op,
                    p.param_value
                FROM UserRoleParameters AS p,
                    Roles AS r
                WHERE user_id = :user_id
                    AND r.abbrev = :abbrev
                    AND r.role_id = p.role_id
                    AND p.is_active = 1
            ";
            $query_params = array(
                ':user_id' => $user->getUserID(),
                ':abbrev' => $this->getIdentifier(),
            );
        }

        $dbh = DB::factory('database');

        $results = $dbh->query($query, $query_params);

        foreach ($results as $result) {
            $this->addParameter($result['param_name'], $result['param_value']);
        }
    }

    /**
     * Get the user id of the user this role belongs to.
     *
     * @return int
     */
    public function getCorrespondingUserID()
    {

        // A new user has been created, yet not saved.
        if ($this->_user_id == null) {
            throw new \Exception(
                'No user ID has been assigned to this role.  You must call'
                . ' configure() before calling getCorrespondingUserID()'
            );
        }

        return $this->_user_id;
    }

    /**
     * Role parameter accessor.
     *
     * @return array
     */
    public function getParameters()
    {
        return $this->_params;
    }

    /**
     * Add a parameter.
     *
     * @param string $param_name
     * @param mixed $param_value
     */
    protected function addParameter($param_name, $param_value)
    {
        $this->_params[$param_name] = $param_value;
    }

    /**
     * Permitted modules accessor.
     *
     * @return array
     */
    public function getPermittedModules()
    {
        return $this->_permittedModules;
    }

    /**
     * Add a module to the array of permitted modules.
     *
     * @param \User\Elements\Module $module
     */
    protected function addPermittedModule(\User\Elements\Module $module)
    {
        $this->_permittedModules[] = $module;
    }

    /**
     * @deprecated Does not appear to be used anywhere.
     */
    public function getMyUsageMenus()
    {
        return $this->_myUsageMenus;
    }

    /**
     * Add a query descripter.
     *
     * @param \User\Elements\QueryDescripter $query_descripter
     */
    public function addQueryDescripter(
        \User\Elements\QueryDescripter $query_descripter
    ) {
        $query_groupname     = $query_descripter->getQueryGroupname();
        $query_realm         = $query_descripter->getRealmName();
        $query_group_by_name = $query_descripter->getGroupByName();

        if (!isset($this->_querys[$query_groupname])) {
            $this->_querys[$query_groupname] = array();
        }

        if (!isset($this->_querys[$query_groupname][$query_realm])) {
            $this->_querys[$query_groupname][$query_realm] = array();
        }

        if (!isset($this->_querys[$query_groupname][$query_realm][$query_group_by_name])) {
            $this->_querys[$query_groupname][$query_realm][$query_group_by_name]
                = array();
        }

        $statistic_name
            = $query_descripter->getDefaultStatisticName() === 'all'
            ? 'all'
            : $query_descripter->getDefaultStatisticName() . '-'
                . $query_descripter->getDefaultQueryType();

        $this->_querys[$query_groupname][$query_realm][$query_group_by_name][$statistic_name]
            = $query_descripter;
    }

    /**
     * Query descripter accessor.
     *
     * @param string $query_groupname
     * @param string|null $realm_name
     * @param string|null $group_by_name
     * @param string|null $statistic_name
     * @param bool $flatten
     *
     * @return \User\Elements\QueryDescripter
     */
    public function getQueryDescripters(
        $query_groupname,
        $realm_name = null,
        $group_by_name = null,
        $statistic_name = null,
        $flatten = false
    ) {
        if ($query_groupname === 'my_usage') {
            $query_groupname = 'tg_usage';
        }

        if (isset($this->_querys[$query_groupname])) {
            if (isset($realm_name)) {
                if (isset($this->_querys[$query_groupname][$realm_name])) {
                    if (isset($group_by_name)) {
                        if (isset($this->_querys[$query_groupname][$realm_name][$group_by_name])) {
                            if (isset($statistic_name)) {
                                if (isset($this->_querys[$query_groupname][$realm_name][$group_by_name][$statistic_name . '-timeseries'])) {
                                    return $this->_querys[$query_groupname][$realm_name][$group_by_name][$statistic_name . '-timeseries'];
                                } elseif (isset($this->_querys[$query_groupname][$realm_name][$group_by_name][$statistic_name . '-aggregate'])) {
                                    return $this->_querys[$query_groupname][$realm_name][$group_by_name][$statistic_name . '-aggregate'];
                                } else {
                                    $qd = $this->_querys[$query_groupname][$realm_name][$group_by_name]['all'];
                                    $qd->setDefaultStatisticName($statistic_name);

                                    return $qd;
                                }
                            } else {
                                return $this->_querys[$query_groupname][$realm_name][$group_by_name]['all'];
                            }
                        } else {
                            return array();
                        }
                    } else {
                        // No group name specified.

                        if ($flatten) {
                            $ret = array();

                            foreach ($this->_querys[$query_groupname][$realm_name] as $query_descripters_group_realm) {
                                foreach ($query_descripters_group_realm as $query_descripter) {
                                    $ret[] = $query_descripter;
                                }
                            }

                            $order_column = array();

                            foreach ($ret as $key => $query_descripter) {
                                $order_column[$key]  = $query_descripter->getOrderId();
                            }

                            array_multisort($order_column, SORT_ASC, $ret);

                            return $ret;
                        } else {
                            return $this->_querys[$query_groupname][$realm_name];
                        }
                    }
                } else {
                    // No queries for the specified realm.
                    return array();
                }
            } else {
                // No realm specified.

                if ($flatten) {
                    $ret = array();

                    foreach ($this->_querys[$query_groupname] as $query_descripters_in_query_group) {
                        foreach ($query_descripters_in_query_group as $query_descripters_group_realm) {
                            foreach ($query_descripters_group_realm as $query_descripter) {
                                $ret[] = $query_descripter;
                            }
                        }
                    }

                    $order_column = array();

                    foreach ($ret as $key => $query_descripter) {
                        $order_column[$key]  = $query_descripter->getOrderId();
                    }

                    array_multisort($order_column, SORT_ASC, $ret);

                    return $ret;
                } else {
                    return $this->_querys[$query_groupname];
                }
            }
        }

        return array();
    }

    /**
     * Check if this role has access to the requested data.
     *
     * @param  string  $query_groupname The query group name.
     * @param  string  $realm_name      (Optional) The realm name.
     * @param  string  $group_by_name   (Optional) The group by name.
     * @param  string  $statistic_name  (Optional) The statistic name.
     * @return boolean                  True if the role is authorized.
     *                                  Otherwise, false.
     */
    public function hasDataAccess(
        $query_groupname,
        $realm_name = null,
        $group_by_name = null,
        $statistic_name = null
    ) {
        $queryDescriptors = $this->getQueryDescripters(
            $query_groupname,
            $realm_name,
            $group_by_name,
            $statistic_name
        );

        if (!is_array($queryDescriptors)) {
            $queryDescriptors = array($queryDescriptors);
        }

        $availableQueryDescriptors = array();
        foreach ($queryDescriptors as $queryDescriptor) {
            if ($queryDescriptor->getDisableMenu()
            ) {
                continue;
            }

            $availableQueryDescriptors[] = $queryDescriptor;
        }

        return !empty($availableQueryDescriptors);
    }

    /**
     * Returns all the query realms for the specified query groupname.
     *
     * @param string $query_groupname
     *
     * @return array
     */
    public function getAllQueryRealms($query_groupname)
    {
        if (isset($this->_querys[$query_groupname])) {
            return $this->_querys[$query_groupname];
        }

        return array();
    }

    /**
     * Returns all the query groupnames.
     *
     * @return array
     */
    public function getAllGroupNames()
    {
        return array_keys($this->_querys);
    }

    /**
     * Returns the formal name of this role.
     *
     * @return string
     */
    public function getFormalName()
    {
        $pdo = DB::factory('database');

        $roleData = $pdo->query(
            "
                SELECT description
                FROM Roles
                WHERE abbrev = :abbrev
            ",
            array(
                ':abbrev' => $this->_identifier,
            )
        );

        if (count($roleData) == 0) {
            return '';
        }

        return $roleData[0]['description'];
    }

    /**
     * Returns the role identifier initially passed into the constructor
     * on behalf of the child classes.  If $absolute_identifier is set
     * to true and the role is organization-specific, that organization
     * data will be appended to the identifier. (e.g. 'cd;574' as
     * opposed to simply 'cd') -- this logic is implemented in the role
     * definitions themselves.
     */
    public function getIdentifier($absolute_identifier = false)
    {
        return $this->_identifier;
    }

    /**
     * The factory method will determine which Role definition to load,
     * based on the value of $role. The role object returned can then
     * take user data into account when determining proper parameters
     * (by means of consulting moddb.UserRoleParameters).
     */
    public function getRoleCategories($exclude_xsede_category = false)
    {
        if ($exclude_xsede_category == true) {
            unset($this->_roleCategories['tg']);
        }

        return $this->_roleCategories;
    }

    /**
     * Returns an array of all the disabled menus for this role.
     *
     * @param array $realms
     *
     * @return array
     */
    public function getDisabledMenus($realms)
    {
        $returnData = array();

        foreach ($realms as $realm_name) {
            $query_descripter_groups = $this->getQueryDescripters(
                'tg_usage',
                $realm_name
            );

            foreach ($query_descripter_groups as $query_descripter_group) {
                foreach ($query_descripter_group as $query_descripter) {
                    if ($query_descripter->getShowMenu() !== true) {
                        continue;
                    }

                    if ($query_descripter->getDisableMenu()) {
                        $returnData[] = array(
                            'id'       => 'group_by_' . $realm_name . '_'
                                        . $query_descripter->getGroupByName(),
                            'group_by' => $query_descripter->getGroupByName(),
                            'realm'    => $realm_name
                        );
                    }
                }
            }
        }

        return $returnData;
    }

    /**
     * Returns the summary charts config data for this role.
     *
     * @return array
     */
    public function getSummaryCharts()
    {
        return array_map(
            function ($chart) {
                return json_encode($chart);
            },
            self::getConfig($this->_identifier, 'summary_charts')
        );
    }
}
