<?php

namespace CCR;

use Monolog\Formatter\LineFormatter;

class CCRLineFormatter extends LineFormatter
{
    /**
     * We've overridden this function so that we can customize the way that arrays of data are displayed from the
     * standard `{"key" => "value", ...}` to `(key: value)`
     *
     * @param mixed $data
     * @param bool $ignoreErrors
     * @return string
     */
    protected function toJson($data, $ignoreErrors = false): string
    {
        $parts = [];
        if (count($data) > 0) {
            $nonMessageParts = array();

            foreach ($data as $key => $value) {
                $nonMessageParts[] = "$key: $value";
            }

            $parts[] = '(' . implode(', ', $nonMessageParts) . ')';
        }

        return implode(' ', $parts);
    }
}
