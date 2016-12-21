<?php

namespace User;

/*
* @author: Amin Ghadersohi
* @date: 2011-01-15
*
* The abstract class, aRole, implements the following methods, which can be subequenty overridden
* by the Role definition classes (e.g. UserRole, CenterDirectorRole, etc...)
*
*/

interface iRole
{

    // parameters: Parameters associated with a user and the role of interest
    //  @returns array
    public function getParameters();



    // -----------------------------------

    // permittedModules: i.e. The tabs to be presented for a particular role
    //  @returns array
    public function getPermittedModules();

    public function getQueryDescripters($query_groupname, $realm_name = NULL, $group_by_name = NULL, $statistic_name = NULL, $flatten = false);

    public function getAllQueryRealms($query_groupname);

    public function getSummaryCharts();
} //iRole

?>
