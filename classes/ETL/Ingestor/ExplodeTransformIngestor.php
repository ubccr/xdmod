<?php
/* ==========================================================================================
 * ExplodeTransform. This ingestor uses the php explode() function to split the
 * contents of a single source column into multiple destination columns.
 *
 * The name of the source to split and the destination column to populate
 * are specified using the explode_column configuration property. All other
 * source columns are passed unmodified.
 */
namespace ETL\Ingestor;

use ETL\aOptions;
use ETL\iAction;
use ETL\aAction;
use ETL\Configuration\EtlConfiguration;
use ETL\EtlOverseerOptions;

use Log;

class ExplodeTransformIngestor extends pdoIngestor implements iAction
{
    /**
     * The name of the column in the source table to explode().
     */
    private $srcKey;
    /**
     * The name of the column in the destination table populate.
     */
    private $destKey;

    /**
     * @see ETL\Ingestor\pdoIngestor::__construct()
     */
    public function __construct(aOptions $options, EtlConfiguration $etlConfig, Log $logger = null)
    {
        parent::__construct($options, $etlConfig, $logger);

        $this->verifyRequiredConfigKeys(array('explode_column'), $options);

        foreach($options->explode_column as $key => $value) {
            $this->srcKey = $key;
            $this->destKey = $value;
            break;
        }
    }

    /**
     * @see ETL\Ingestor\pdoIngestor::transform()
     */
    protected function transform(array $srcRecord, $orderId)
    {
        $transformedRecord = array();
        $items = explode(',', $srcRecord[$this->srcKey]);

        foreach ($items as $item) {
            $out = array(
                $this->destKey => $item
            );
            foreach($srcRecord as $key => $value) {
                if ($key != $this->destKey && $key != $this->srcKey) {
                    $out[$key] = $value;
                }
            }
            $transformedRecord[] = $out;
        }
        return $transformedRecord;
    }
}
