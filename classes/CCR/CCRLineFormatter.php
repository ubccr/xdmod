<?php

namespace CCR;

use Monolog\Formatter\LineFormatter;
use Monolog\Formatter\NormalizerFormatter;
use Monolog\LogRecord;

class CCRLineFormatter extends LineFormatter
{

    private NormalizerFormatter $normalizerFormatter;

    public function __construct($format = null, $dateFormat = null, $allowInlineLineBreaks = false, $ignoreEmptyContextAndExtra = false)
    {
        if (!str_contains($format, '%context%')) {
            // first strip newlines from the format, then we'll add one at the end.
            $format = str_replace(["\n", "\r\n", "\r",], '', $format);
            $format = "$format%context%\n";
        }
        $this->normalizerFormatter = new NormalizerFormatter($dateFormat);
        parent::__construct($format, $dateFormat, $allowInlineLineBreaks, $ignoreEmptyContextAndExtra);
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

    public function format(LogRecord $record): string
    {
        $vars = $this->normalizerFormatter->format($record);

        if ($this->maxLevelNameLength !== null) {
            $vars['level_name'] = substr($vars['level_name'], 0, $this->maxLevelNameLength);
        }

        // make sure that the level name is lower case, this is for legacy reasons.
        $vars['level_name']= strtolower($vars['level_name']);

        $output = $this->format;
        foreach ($vars['extra'] as $var => $val) {
            if (false !== strpos($output, '%extra.'.$var.'%')) {
                $output = str_replace('%extra.'.$var.'%', $this->stringify($val), $output);
                unset($vars['extra'][$var]);
            }
        }

        foreach ($vars['context'] as $var => $val) {
            if (false !== strpos($output, '%context.'.$var.'%')) {
                $output = str_replace('%context.'.$var.'%', $this->stringify($val), $output);
                unset($vars['context'][$var]);
            }
        }

        if ($this->ignoreEmptyContextAndExtra) {
            if (\count($vars['context']) === 0) {
                unset($vars['context']);
                $output = str_replace('%context%', '', $output);
            }

            if (\count($vars['extra']) === 0) {
                unset($vars['extra']);
                $output = str_replace('%extra%', '', $output);
            }
        }

        foreach ($vars as $var => $val) {
            if (false !== strpos($output, '%'.$var.'%')) {
                $output = str_replace('%'.$var.'%', $this->stringify($val), $output);
            }
        }

        if (false !== strpos($output, '%context%')) {
            $output = str_replace($output, '%context%', $this->toJson($vars['context']));
        }

        // remove leftover %extra.xxx% and %context.xxx% if any
        if (false !== strpos($output, '%')) {
            $output = preg_replace('/%(?:extra|context)\..+?%/', '', $output);
            if (null === $output) {
                $pcreErrorCode = preg_last_error();

                throw new \RuntimeException('Failed to run preg_replace: ' . $pcreErrorCode . ' / ' . preg_last_error_msg());
            }
        }

        return $output;
    }
}
