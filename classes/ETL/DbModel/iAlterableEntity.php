<?php
/* ==========================================================================================
 * Interface for database model entities that support altering at the source, such as tables.
 *
 * @author Steve Gallo  <smgallo@buffalo.edu>
 * @date 2017-05-04
 * ==========================================================================================
 */

namespace ETL\DbModel;

interface iAlterableEntity
{
    /* ------------------------------------------------------------------------------------------
     * Generate SQL to transform the this entity into the destination entity. For example,
     * generate the ALTER TABLE statements required to transform this Table into the
     * destination Table. More than one statement may be returned if required. For
     * example, triggers are handled separately from the ALTER TABLE statement. The
     * $destination and $includeSchema parameters are the minimum information needed for
     * discovery and it is likely that additional parameters will be needed, but this is
     * up to the implementation to specify.
     *
     * @param mixed $destination The entity that containing a defintion that we would like
     *   to achieve.
     * @param bool $includeSchema TRUE to include the schema in the entity names, if
     *    appropriate.
     *
     * @return An array comtaining all SQL statements required for altering this item.
     *
     * @throw Exception If there is an error during the process
     * ------------------------------------------------------------------------------------------
     */

    public function getAlterSql($destination, $includeSchema = true);
}  // interface iAlterableEntity
