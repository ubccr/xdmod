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

use \Log;

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
     * Perform verification on this action before executing it, allowing us to detect configuration
     * errors. We pass the EtlOverseerOptions here because verification can occur pre-execution. Since
     * multiple classes may implement verify(), null may be passed to parent::verify() so there are
     * not multiple assignments of large objects.
     *
     * @param EtlOverseerOptions $etlOptions Options set for this ETL run. This may be null if it was
     *   set elsewhere in the chain
     *
     * @return TRUE if verification was successful
     * ------------------------------------------------------------------------------------------
     */
  
    public function verify(EtlOverseerOptions $etlOptions = null);

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
     * @return TRUE if verification has been performed on this action.
     * ------------------------------------------------------------------------------------------
     */
  
    public function isVerified();

    /* ------------------------------------------------------------------------------------------
     * Generate a string representation of the action. Typically the name, plus other pertinant
     * information as appropriate such as the implementing class.
     *
     * @return A string representation of the endpoint
     * ------------------------------------------------------------------------------------------
     */

    public function __toString();
}  // interface iAction
