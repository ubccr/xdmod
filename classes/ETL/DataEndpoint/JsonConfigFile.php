<?php
/**
 * JSON config file data endpoint. Supports reading a valid json file.
 */

namespace ETL\DataEndpoint;

class JsonConfigFile extends JsonFile
{
    /**
     * @const string Defines the name for this endpoint that should be used in configuration files.
     * It also allows us to implement auto-discovery.
     */

    const ENDPOINT_NAME = 'jsonconfigfile';

    /**
     * A configuration file contain a single json object that can contain
     * arbirtrary structure. THis is completely unlike the type of Json
     * file that is parsed by the JsonFile class. The JsonFile class is
     * designed for parsing structured record-based data in json format.
     */
    protected function decodeRecord($data)
    {
        $decoded = @json_decode($data);

        if ( null === $decoded ) {
            $this->logAndThrowException(
                sprintf(
                    "Error decoding JSON from file '%s': %s\n%s",
                    $this->path,
                    $this->jsonLastErrorMsg(json_last_error()),
                    $data
                )
            );
        }

        $this->recordList = array($decoded);

        return true;
    }


    /**
     * No discovery of record field names is performed since there is no
     * concept of multiple records in a configuration file.
     */

    protected function discoverRecordFieldNames()
    {
        return;
    }

    /**
     * This is a simple passthrough since there is no concept of records
     * or record fields in a configuration file.
     */

    protected function createReturnRecord($record): mixed
    {
        return $record;
    }
}
