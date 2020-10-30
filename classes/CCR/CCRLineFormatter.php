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

        return parent::format($record);
    }

}
