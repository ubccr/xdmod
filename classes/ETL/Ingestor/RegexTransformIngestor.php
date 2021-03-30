<?php
/* ==========================================================================================
 * RegexTransform. This ingestor transforms values using the preg_filter() function.
 *
 * The regular expressions, name of the source to split and the destination column
 * to populate are specified using configuration properties. All other source
 * columns are passed unmodified. If the regular expression does not match then
 * the row is not passed by the ingestor.
 *
 * Configuration properties:
 *
 * - regex_column: defines the column in the source table to read and column name in the
 *                 output to use for the transformed data. For example:
 *                 { "source": "dest" } would read the data in column named "source" and
 *                 the transformed content of "source" would be written to "dest".
 * - regex_config: a json formatted string that contains regular expression and output
 *                 patterns. The the regex format is the one used by preg_filter().
 *                 For example:
 *                     {
 *                         "#foo_([a-z]+)$#": "bar_$1"
 *                     }
 *                 defines a regex that matches foo_ and any lowercase letters and then transforms
 *                 it to bar_ with the same letters.
 *
 */
namespace ETL\Ingestor;

use ETL\aOptions;
use ETL\iAction;
use ETL\Configuration\EtlConfiguration;

use Psr\Log\LoggerInterface;

class RegexTransformIngestor extends pdoIngestor implements iAction
{
    /**
     * The name of the column in the source table to explode().
     */
    private $srcKey;
    /**
     * The name of the column in the destination table populate.
     */
    private $destKey;

    /*
     * Array of regular expressions to test.
     */
    private $regexconf;

    /**
     * @see ETL\Ingestor\pdoIngestor::__construct()
     */
    public function __construct(aOptions $options, EtlConfiguration $etlConfig, LoggerInterface $logger = null)
    {
        parent::__construct($options, $etlConfig, $logger);

        $this->verifyRequiredConfigKeys(array('regex_config', 'regex_column'), $options);

        foreach($options->regex_column as $key => $value) {
            $this->srcKey = $key;
            $this->destKey = $value;
            break;
        }

        $rconf = json_decode($options->regex_config, true);

        $this->patterns = array_keys($rconf);
        $this->replacements = array_values($rconf);
    }

    /**
     * @see ETL\Ingestor\pdoIngestor::transform()
     */
    protected function transform(array $srcRecord, $orderId)
    {
        $transformedRecord = array();

        $res = preg_filter($this->patterns, $this->replacements, $srcRecord[$this->srcKey]);

        if ($res !== null) {
            $outdata = $srcRecord;
            $outdata[$this->destKey] = $res;

            $transformedRecord[] = $outdata;
        } else {
            $this->logger->debug("Ignore " . $srcRecord[$this->srcKey]);
        }

        return $transformedRecord;
    }
}
