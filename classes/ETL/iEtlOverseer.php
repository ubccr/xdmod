<?php
/* ==========================================================================================
 * Interface that an ETL Overseers must implement.
 *
 * @author Steve Gallo <smgallo@buffalo.edu>
 * @date 2015-09-15
 * ==========================================================================================
 */

namespace ETL;

interface iEtlOverseer
{
    /* ------------------------------------------------------------------------------------------
     * General setup including options.
     *
     * @param EtlOverseerOptions $options Overseer options including start/end time.
     * ------------------------------------------------------------------------------------------
     */

    public function __construct(EtlOverseerOptions $options);

    /* ------------------------------------------------------------------------------------------
     * Verify that all enabled data sources are properly configured and we can connect to them.
     *
     * @param bool $leaveConnected TRUE if the endpoints should be left in the connected after
     * verification.
     *
     * @return TRUE on success.
     *
     * @throws Exception if there was an error connecting to any endpoint.
     * ------------------------------------------------------------------------------------------
     */

    public function verifyDataEndpoints(EtlConfiguration $config, $leaveConnected = false);

    /* ------------------------------------------------------------------------------------------
     * Verify that all enabled actions in the action name list are properly configured.
     *
     * @param $etlConfig An EtlConfiguration object containing the parsed ETL configuration
     * @param $actionNameList The list of action names to verify
     * @param $actionObjectList An optional associative array of (action name, action object)
     *   pairs. This is used to check for actions that have already been verified.
     * @param $sectionName An optional section name to be used when looking up action options
     * @param bool $verifyDisabled true if actions that are marked as disabled should be checked as
     *   well.
     *
     * @return The updated list of verified action objects
     *
     * @throws Exception if there was an error validating any action.
     * ------------------------------------------------------------------------------------------
     */

    public function verifyActions(
        EtlConfiguration $config,
        array $actionNameList,
        array $actionObjectList = array(),
        $sectionName = null,
        $verifyDisabled = false
    );

    /* ------------------------------------------------------------------------------------------
     * Verify that all enabled actions in the specified section are properly configured.
     *
     * @param $etlConfig An EtlConfiguration object containing the parsed ETL configuration
     * @param $sectionNameList An array of section names to verify
     * @param $sectionActionObjectList A associative array where the keys are section names and the
     *   values are an associative array of (action name, action object) pairs. This is used to
     *   check for actions that have already been verified.
     * @param bool $verifyDisabled true if actions that are marked as disabled should be checked as
     *   well.
     *
     * @return The updated list of verified action objects for each section
     *
     * @throws Exception if there was an error validating any action.
     * ------------------------------------------------------------------------------------------
     */

    public function verifySections(
        EtlConfiguration $config,
        array $sectionNameList,
        array $sectionActionObjectList = array(),
        $verifyDisabled = false
    );

    /* ------------------------------------------------------------------------------------------
     * Perform ingestion based on the overseer configuration file.
     *
     * @param EtlConfiguration $etlConfig The parsed ETL configuration object.
     * ------------------------------------------------------------------------------------------
     */

    public function execute(EtlConfiguration $config);
}  // interface iEtlOverseer
