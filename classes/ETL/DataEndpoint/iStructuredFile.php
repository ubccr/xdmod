<?php
/* ==========================================================================================
 * Interface for handling structured files. Structured files contain data in a known
 * (structured) format including CSV, tab-delimited, and JSON. Records are separated by a
 * Record Separator (RS) and individual fields may be separated by a Field Separator (FS)
 * if the format allows or requires it (e.g., CSV and TSV). Typically, we will read a
 * structured file by iterating over the records and operating on the fields within the
 * record. Each record returned by the iterator methods must be traversable (either an
 * array, object, or implementing the Traversable interface)
 *
 * @author Steve Gallo  <smgallo@buffalo.edu>
 * @date 2017-05-10
 * ==========================================================================================
 */

namespace ETL\DataEndpoint;

interface iStructuredFile extends iFile, \Iterator, \Countable
{

    /** -----------------------------------------------------------------------------------------
     * @return string The record separator, or NULL if none has been set.
     * ------------------------------------------------------------------------------------------
     */

    public function getRecordSeparator();

    /** -----------------------------------------------------------------------------------------
     * @return string The field separator, or NULL if none has been set.
     * ------------------------------------------------------------------------------------------
     */

    public function getFieldSeparator();

    /** -----------------------------------------------------------------------------------------
     * @return boolean TRUE if the file is expected to have a header record, FALSE otherwise.
     * ------------------------------------------------------------------------------------------
     */

    public function hasHeaderRecord();

    /** -----------------------------------------------------------------------------------------
     * @return array The list of field names that will be returned for each record. These
     * are set via the field_names option to the StructuredFile endpoint.
     * ------------------------------------------------------------------------------------------
     */

    public function getRequestedRecordFieldNames();

    /** -----------------------------------------------------------------------------------------
     * @return array The list of field names that are present for a record. These are
     * either specified or auto-discovered (via a header record or record object keys, for
     * example).
     * ------------------------------------------------------------------------------------------
     */

    public function getExistingRecordFieldNames();

    /** -----------------------------------------------------------------------------------------
     * @return array An associative array of filter configuration objects to be applied to
     *   the data in this file. The key is the filter key used in the configuration.
     * ------------------------------------------------------------------------------------------
     */

    public function getAttachedFilters();

    /** -----------------------------------------------------------------------------------------
     * Parse and possibly decode the (possibly filtered) file data. The resulting data
     * will be stored in an internal data structure and accessible via the iterator
     * methods. The first record will be returned, allowing the implementation of file
     * formats such as a JSON file to return a single object representing simple cases
     * such as a configuration file without needing to use the iterator methods.
     *
     * Note that different types of structured files will represent data differently. For
     * example, a CSV file will typically contain multiple records, one record per
     * line. separated by an end-of-line character. A JSON file could containan a single
     * object (such as a configuration) or an array of objects or scalar data with no
     * record separator. However, a JSON file could also contain multiple individual
     * objects separated by a record separator without the enclosing array.
     *
     * @return mixed The first parsed record parsed from the file. This record must be
     *   iterable, such as an array or an object with public data membbers.
     * ------------------------------------------------------------------------------------------
     */

    public function parse();
}  // interface iStructuredFile
