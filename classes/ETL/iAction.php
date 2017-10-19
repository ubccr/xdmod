<?php
/* ==========================================================================================
 * An individual action performs some task, such as an ingestor or aggregator. iAction defines the
 * required methods for implementation. The aAction abstract helper class encapsulates some common
 * functionality and should be extended by action implementations.
 *
 * @author Steve Gallo <smgallo@buffalo.edu>
 * @date 2015-11-01
 *
 * @see aAction
 * ==========================================================================================
 */

namespace ETL;

use ETL\Configuration\EtlConfiguration;
use Log;

interface iAction
{
    /* ------------------------------------------------------------------------------------------
     * Set up the action. This typically entails verifying that the data endpoints are of the
     * correct type and setting up configuration and option information.
     *
     * @param IngestorOptions $options Options specific to this action, typically parsed from the
     *   ETL configuration file.
     * @param EtlConfiguration $etlConfig The complete ETL configuration as parsed from the
     *   configuration file.
     * ------------------------------------------------------------------------------------------
     */

    public function __construct(aOptions $options, EtlConfiguration $etlConfig, Log $logger = null);

    /* ------------------------------------------------------------------------------------------
     * Execute the action.
     *
     * @param EtlOverseerOptions $etlOptions Options set for this ETL run.
     * ------------------------------------------------------------------------------------------
     */

    public function execute(EtlOverseerOptions $etlOptions);

    /* ------------------------------------------------------------------------------------------
     * Perform initialization and verification on this action before executing it,
     * allowing us to detect configuration errors. We pass the EtlOverseerOptions here
     * because verification may occur pre-execution. Since multiple classes may implement
     * iAction::initialize(), null may be passed to parent::initialize() so there are not
     * multiple assignments of large objects.
     *
     * This must occur AFTER the constructor calls has completed and should be called
     * prior to verification and/or execution.
     *
     * @param EtlOverseerOptions $etlOverseerOptions Options set for this ETL run. This may be null
     *   if it was set elsewhere in the chain.
     *
     * @return TRUE if verification was successful
     * ------------------------------------------------------------------------------------------
     */

    public function initialize(EtlOverseerOptions $etlOverseerOptions = null);

    /* ------------------------------------------------------------------------------------------
     * @return The name of the action.
     * ------------------------------------------------------------------------------------------
     */

    public function getName();

    /* ------------------------------------------------------------------------------------------
     * @return The class name that implements the action.
     * ------------------------------------------------------------------------------------------
     */

    public function getClass();

    /* ------------------------------------------------------------------------------------------
     * @return The action options (aOptions)
     * ------------------------------------------------------------------------------------------
     */

    public function getOptions();

    /* ------------------------------------------------------------------------------------------
     * @return The current start date that this action is operating on.
     * ------------------------------------------------------------------------------------------
     */

    public function getCurrentStartDate();

    /* ------------------------------------------------------------------------------------------
     * @return The current start date that this action is operating on.
     * ------------------------------------------------------------------------------------------
     */

    public function getCurrentEndDate();

    /* ------------------------------------------------------------------------------------------
     * @return TRUE if this action supports chunking of the overall ETL start and end date
     *   into smaller pieces to make it more manageable.
     * ------------------------------------------------------------------------------------------
     */

    public function supportsDateRangeChunking();

    /* ----------------------------------------------------------------------------------------------------
     * The ETL overseer provides the ability to specify parameters that are interpreted as
     * restrictions on actions such as the ETL start/end dates and resources to include or
     * exclude from the ETL process.  However, in some cases these options may be
     * overriden by the configuration of an individual action such as resources to include
     * or exclude for that action.
     *
     * @return An associative array of optional overrides to overseer restrictions.
     * ----------------------------------------------------------------------------------------------------
     */

    public function getOverseerRestrictionOverrides();

    /* ------------------------------------------------------------------------------------------
     * @return TRUE if initialization has been performed on this action.
     * ------------------------------------------------------------------------------------------
     */

    public function isInitialized();

    /* ------------------------------------------------------------------------------------------
     * Generate a string representation of the action. Typically the name, plus other pertinant
     * information as appropriate such as the implementing class.
     *
     * @return A string representation of the endpoint
     * ------------------------------------------------------------------------------------------
     */

    public function __toString();
}  // interface iAction
