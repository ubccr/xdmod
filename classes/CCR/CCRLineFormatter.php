<?php

namespace CCR;

use Monolog\Formatter\LineFormatter;

class CCRLineFormatter extends LineFormatter
{
    public function format(array $record):string
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
    protected function extractMessage($record): string
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

                foreach ($json as $key => $value) {
                    $nonMessageParts[] = "$key: $value";
                }

                $parts[] = '(' . implode(', ', $nonMessageParts) . ')';
            }

            $record['message'] = implode(' ', $parts);
        }

        /* Otherwise, we assume the message is a string. */
        return $record['message'];
    }

    /**
     * @see LineFormatter::replaceNewlines
     */
    protected function replaceNewlines($str): string
    {
        return str_replace(array('\r', '\n'), array("\r", "\n"), $str);
    }
}
