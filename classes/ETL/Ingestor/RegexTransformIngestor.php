<?php
/* ==========================================================================================
 * RegexTransform. This ingestor transforms values using the preg_filter() function.
 *
 * The regular expressions, names of the sources to split and the destination columns
 * to populate are specified using configuration properties. All other source
 * columns are passed unmodified. If no regular expressions match then
 * the row is not passed by the ingestor.
 *
 * Configuration properties:
 *
 * - regex_column: defines the columns in the source table to read and column names in the
 *                 output to use for the transformed data. For example:
 *                 { "dest1": "source1", "dest2": "source2" } would read the data in column named
 *                 "source1" and the transformed content of "source1" would be written to "dest1",
 *                 then the transformed content of "source2" would be written to "dest2". If "dest1"
 *                 and "source2" are the same, "dest2" will transform the old value of "source2"
 *                 (old meaning before it was transformed from "source1" to "dest1").
 * - regex_config: mapping of destination column names to json formatted strings that contain
 *                 regular expression and output patterns. The regex format is the one used by
 *                 preg_filter().
 *                 For example:
 *                     {
 *                         "dest": {
 *                             ";foo_([a-z]+)$;": "bar_$1"
 *                         }
 *                     }
 *                 defines a regex that matches foo_ and any lowercase letters and then transforms
 *                 it to bar_ with the same letters, storing the result in the "dest" column.
 */
namespace ETL\Ingestor;

use ETL\aOptions;
use ETL\iAction;
use ETL\Configuration\EtlConfiguration;

use Psr\Log\LoggerInterface;

class RegexTransformIngestor extends pdoIngestor implements iAction
{
    private $regex_column;
    private $dest_configs;

    /**
     * @see ETL\Ingestor\pdoIngestor::__construct()
     */
    public function __construct(aOptions $options, EtlConfiguration $etlConfig, LoggerInterface $logger = null)
    {
        parent::__construct($options, $etlConfig, $logger);

        $this->verifyRequiredConfigKeys(array('regex_column', 'regex_config'), $options);

        $this->regex_column = $options->regex_column;
        $this->dest_configs = array();
        foreach ($options->regex_config as $dest => $config) {
            $this->dest_configs[$dest] = get_object_vars($config);
        }
    }

    /**
     * @see ETL\Ingestor\pdoIngestor::transform()
     */
    protected function transform(array $srcRecord, &$orderId)
    {
        $transformedRecord = array();
        $outdata = $srcRecord;
        $ignored = true;
        foreach ($this->dest_configs as $dest => $config) {
            $srcColumn = $srcRecord[$this->regex_column->{$dest}];
            $res = preg_filter(
                array_keys($config),
                array_values($config),
                $srcColumn
            );

            if (!is_null($res)) {
                $outdata[$dest] = $res;
                $ignored = false;
            }
        }
        if ($ignored) {
            $this->logger->warning('RegexTransformIngestor ignoring ' . implode('|', $srcRecord));
        } else {
            $transformedRecord[] = $outdata;
        }
        return $transformedRecord;
    }
}
