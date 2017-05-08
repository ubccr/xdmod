<?php
/* ==========================================================================================
 * Interface for database model entities that are discoverable, meaning that they can
 * examine an external source to set their configuration. This includes constructing a
 * Table by examining the information schema of a database or building a Query by
 * examining an SQL statement.
 *
 * @author Steve Gallo  <smgallo@buffalo.edu>
 * @date 2017-05-04
 * ==========================================================================================
 */

namespace ETL\DbModel;

interface iDiscoverableEntity
{
    /* ------------------------------------------------------------------------------------------
     * Examine the source and build a prepresentation of the entity in this object based
     * on the information found. If this object already contained a representation, it
     * will be removed and replaced with the discovered representation. The $source
     * parameter is the minimum information needed for discovery and it is likely that
     * additional parameters will be needed, but this is up to the implementation to
     * specify.
     *
     * @param mixed $source The source that we will query. This could be a file path or a
     *   table name.
     *
     * @return TRUE on success
     *
     * @throw Exception If there is an error during the discovery process
     * ------------------------------------------------------------------------------------------
     */

    public function discover($source);

}  // interface iDiscoverableEntity
