<?php

namespace CCR;

use Monolog\Formatter\LineFormatter;

class CCRLineFormatter extends LineFormatter
{

    /**
     * This function was extracted from the class `\Log\Log_xdconsole` so that we can keep our log output the same.
     *
     * @param mixed $record
     *
     * @return string
     */
    public static function extractMessage($record)
    {
        $json = null;
        if (is_array($record) && array_key_exists('message', $record) && is_string($record['message'])) {
            $json = json_decode($record['message'], true);
        } elseif (is_array($record)) {
            $json = $record;
        }

        // If we were unable to parse json from $record['message'] or if $record is a string then just return $record;
        if ($json === null || is_string($record)) {
            return $record;
        }

        // If we've made it this far then we should have an associative array.
        $parts = array();

        if (isset($json['message'])) {
            $parts[] = $json['message'];
            unset($json['message']);
        }

        if (count($json) > 0) {
            $nonMessageParts = array();

            foreach ($json as $key => $value) {
                $nonMessageParts[] = "$key: $value";
            }

            $parts[] = '(' . implode(', ', $nonMessageParts) . ')';
        }

        return implode(' ', $parts);
    }

    /**
     * @see LineFormatter::replaceNewlines
     */
    protected function replaceNewlines($str): string
    {
        return str_replace(array('\r', '\n'), array("\r", "\n"), $str);
    }
}
