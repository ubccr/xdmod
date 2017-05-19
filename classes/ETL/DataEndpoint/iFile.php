<?php
/* ==========================================================================================
 * Interface for file data endpoints. Files add the ability to specify a path and mode.
 *
 * @author Steve Gallo  <smgallo@buffalo.edu>
 * @date 2017-05-10
 * ==========================================================================================
 */

namespace ETL\DataEndpoint;

interface iFile extends iDataEndpoint
{

    /* ------------------------------------------------------------------------------------------
     * @return The path to the file
     * ------------------------------------------------------------------------------------------
     */

    public function getPath();

    /* ------------------------------------------------------------------------------------------
     * @return The mode used to access the file specified by the path.
     * ------------------------------------------------------------------------------------------
     */

    public function getMode();
}  // interface iFile
