<?php

namespace CCR;

use Monolog\Formatter\LineFormatter;
use Monolog\Formatter\NormalizerFormatter;
use Monolog\Utils;

class CCRLineFormatter extends LineFormatter
{
    public function __construct($format = null, $dateFormat = null, $allowInlineLineBreaks = false, $ignoreEmptyContextAndExtra = false)
    {
        parent::__construct($format, $dateFormat, $allowInlineLineBreaks, $ignoreEmptyContextAndExtra);
        $this->includeStacktraces();
    }

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

    /**
     * Formatter for XDMoD Line based logging. This handles the line format
     * parameters identically to the parent monolog line formatter except that
     * the %message% parameter is expanded to the serialization of the message
     * string and context object. If either the context is empty or the message
     * is an empty string they are ommitted.
     */
    public function format(array $record)
    {
        $vars = NormalizerFormatter::format($record);

        $output = $this->format;

        foreach ($vars['extra'] as $var => $val) {
            if (false !== strpos($output, '%extra.'.$var.'%')) {
                $output = str_replace('%extra.'.$var.'%', $this->stringify($val), $output);
                unset($vars['extra'][$var]);
            }
        }

        if (false !== strpos($output, '%message%')) {
            $data = [];
            if ($vars['message'] !== '') {
                array_push($data, $vars['message']);
            }
            if (!empty($vars['context'])) {
                array_push($data, $this->stringify($vars['context']));
            }

            $output = str_replace('%message%', implode(" ", $data), $output);
        }

        foreach ($vars['context'] as $var => $val) {
            if (false !== strpos($output, '%context.'.$var.'%')) {
                $output = str_replace('%context.'.$var.'%', $this->stringify($val), $output);
                unset($vars['context'][$var]);
            }
        }

        if ($this->ignoreEmptyContextAndExtra) {
            if (empty($vars['context'])) {
                unset($vars['context']);
                $output = str_replace('%context%', '', $output);
            }

            if (empty($vars['extra'])) {
                unset($vars['extra']);
                $output = str_replace('%extra%', '', $output);
            }
        }

        foreach ($vars as $var => $val) {
            if (false !== strpos($output, '%'.$var.'%')) {
                $output = str_replace('%'.$var.'%', $this->stringify($val), $output);
            }
        }

        // remove leftover %extra.xxx% and %context.xxx% if any
        if (false !== strpos($output, '%')) {
            $output = preg_replace('/%(?:extra|context)\..+?%/', '', $output);
        }

        return $output;
    }
}
