<?php

namespace CCR;


/**
 * An adapter class to be used during the migration to PHP 8 versions of PSR\Logger. Allows instances where we directly
 * logged arrays of data `$logger->error(array('timestamp' => 2934908230498234, 'status' => 'good', 'code' => 200));`
 * as opposed to using something like `sprintf`.
 */
class LogOutput  implements \Stringable
{

    public array $data;

    /**
     * This class isn't meant to be instantiated directly, that's what the `from` method is for.
     *
     * @param array $data
     */
    private function __construct(array $data) {
        $this->data = $data;
    }

    /**
     * Main method for interacting with this class, will wrap an array that was previously provided directly to a Logger
     * method and produce the same output as XDMoD historically expects. This should not be used for new logging calls as
     * PSR\Log functions now accept a $context array that will be logged in much the same way, just in a different format.
     * This is strictly for backwards compatability.
     *
     * @param string|array $data
     *
     * @return LogOutput
     */
    public static function from($data): LogOutput
    {
        if (is_string($data)) {
            return new self(['message' => $data]);
        }
        return new self($data);
    }

    public function __toString(): string
    {
        $results = [];
        $this->recursivelyStringifyObjects($this->data);
        foreach($this->data as $key => $value) {
            $results[]= "$key: $value";
        }
        return implode(', ', $results);
    }

    protected function recursivelyStringifyObjects(&$array)
    {
        foreach ($array as $key => $value) {
            if (is_object($value)) {
                $array[$key] = (string) $value;
            } elseif (is_array($value)) {
                $result = [];
                foreach( $this->recursivelyStringifyObjects($value) as $k => $v) {
                    $result[] = "$k: $v";
                }
                $array[$key] = '(' . implode(', ',$result  ) . ')';
            }
        }
        return $array;
    }
}
