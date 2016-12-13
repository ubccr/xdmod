<?php
/*
 * @author: Amin Ghadersohi 7/1/2010
 *
 */
use Xdmod\Config;

class ProcessorBucketGenerator
{
    function execute($modwdb, $dest_schema)
    {
        $modwdb->handle()->prepare("TRUNCATE TABLE processor_buckets")->execute();

        $config = Config::factory();
        $buckets = $config['processor_buckets'];

        $values = implode(
            ',',
            array_map(
                function ($bucket) {
                    return '(' . implode(
                        ',',
                        array_map(
                            function ($column) { return "'$column'"; },
                            $bucket
                        )
                    ) . ')';
                },
                $buckets
            )
        );

        $modwdb->handle()->prepare("
            INSERT INTO `processor_buckets`
                (`id`, `min_processors`, `max_processors`, `description`)
            VALUES $values
        ")->execute();
    }
}

?>
