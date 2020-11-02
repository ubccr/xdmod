<?php

namespace CCR;

use Monolog\Formatter\LineFormatter;

class CCRLineFormatter extends LineFormatter
{
    public function format(array $record)
    {
        if (isset($record['level_name'])) {
            $record['level_name'] = strtolower($record['level_name']);
        }

        $record['message'] = $this->extractMessage($record);

        return parent::format($record);
    }

    /**
     * This function was extracted from the class `\Log\Log_xdconsole` so that we can keep our log output the same.
     *
     * @param mixed $record
     *
     * @return string
     */
    protected function extractMessage($record)
    {
        $json = json_decode($record['message'], true);

        if ($json !== null)
        {
            $parts = array();

            if (isset($json['message'])) {
                $parts[] = $json['message'];
                unset($json['message']);
            }

            if (count($json) > 0) {
                $nonMessageParts = array();

                while (list($key, $value) = each($json)) {
                    $nonMessageParts[] = "$key: $value";
                }

                $parts[] = '(' . implode(', ', $nonMessageParts) . ')';
            }

            $record['message'] = implode(' ', $parts);
        }

        /* Otherwise, we assume the message is a string. */
        return parent::format($record);
    }

}
