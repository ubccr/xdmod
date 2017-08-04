<?php
/** =========================================================================================
 * The ETL process supports populating multiple destinations from a single source. For example,
 * a single database query or data file can be used to populate multiple destination tables.
 * The destination field map is used to map individual fields in the source record fields to fields
 * in the destination (tables).  By default, the destination map supports simple scalar values for
 * the source record field names (i.e., values in the source fields are mapped directly to
 * destination fields).
 *
 * By implementing this interface a data endpoint indicates that it supports complex data records
 * and implements the methods necessary to handle complex source fields in addition to scalar fields
 * in the destination field map. For example, a JSON data endpoint supports complex nested objects
 * and may allow JSON pointers in the map's source fields to reference data inside the complex
 * object.
 *
 * NOTE: Some classes, such as DirectoryScanner, may delegate their support for complex source
 *   records to underlying classes or handlers. To support this we use
 *   iStructuredFile::supportsComplexDataRecords().
 *
 * For example, the following destination map contains JSON pointers to access the nested object in
 * the JSON record below. Without JSON pointers, there would be no way to programmatically access
 * data other than the top level keys.
 *
 * "destination_field_map": {
 *     "instance_types": {
 *         "id": "instance_id",
 *         "name": "/instance_type/name",
 *         "num_cores": "/instace_type/cpu"
 *     }
 * }
 *
 * {
 *     "id": "887233-bya",
 *     "instance_type": {
 *         "name": "m1.medium",
 *         "num_cores": 8,
 *         "memory": 4096
 *     }
 * }
 *
 * @see iStructuredFile::supportsComplexDataRecords()
 *
 * @author Steve Gallo  <smgallo@buffalo.edu>
 * @datetime 2017-07-19
 * ==========================================================================================
 */

namespace ETL\DataEndpoint;

interface iComplexDataRecords
{
    /** -----------------------------------------------------------------------------------------
     * Perorm validation of the destination map for an INDIVIDUAL table to ensure that the source
     * fields specified in the map are in a supported format and the fields that they evaluate to
     * are present in the fields provided by the source endpoint.
     *
     * @param array $destinationTableMap The mapping between source fields and destination fields
     *   for an INDIVIDUAL table. Keys are destination table fields and values are source record
     *   fields. Note that a source record field may be specified multiple times.
     *
     * @return array An associative array of destination map entries that did NOT pass validation.
     *   The keys are destination fields and the values are source record fields.
     * ------------------------------------------------------------------------------------------
     */

    public function validateDestinationMapSourceFields(array $destinationTableMap);

    /** -----------------------------------------------------------------------------------------
     * Determine if the given field is a complex file as supported by the implementing class.
     *
     * @param string $sourceField The source field to examine.
     *
     * @return boolean TRUE if the field is a complex field, FALSE otherwise.
     * ------------------------------------------------------------------------------------------
     */

    public function isComplexSourceField($sourceField);

    /** -----------------------------------------------------------------------------------------
     * Evaluate a complex source field against a record and return the data in the record referenced
     * by the field. For example, if the field is a JSON pointer return the data in the record
     * referenced by the pointer.
     *
     * @param string $sourceField The complex source field that will be evaluated
     * @param mixed $record The data record that we will reference
     * @param mixed $invalidRefValue The value to return if there was an arror evaluating the source
     *   field. This includes an improperly formatted reference as well as a reference to a field
     *   that does not exist.
     *
     * @return mixed The data in the record referenced by the source field.
     *
     * @throws Exception If the source field was invalid
     * ------------------------------------------------------------------------------------------
     */

    public function evaluateComplexSourceField($sourceField, $record, $invalidReferenceValue = null);
} // interface iComplexDataRecords
