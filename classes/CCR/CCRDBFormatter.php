<?php

namespace CCR;

use Monolog\Formatter\NormalizerFormatter;

class CCRDBFormatter extends NormalizerFormatter
{
    /**
     * Formatter for XDMoD Database logging. This logs a json encoded
     * string that contains the message under the "message" property and
     * all of the properties from the context. If the message is an empty
     * string the message property is not added.
     */
    public function format(array $record)
    {
        $vars = parent::format($record);

        $outdata = array();
        if (strlen($vars['message']) > 0) {
            $outdata['message'] = $vars['message'];
        }
        $outdata = array_merge($outdata, $vars['context']);

        return json_encode($outdata, JSON_NUMERIC_CHECK | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

}
