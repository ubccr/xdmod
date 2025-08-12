<?php

namespace Access\Logging;

class LogOutput implements \Stringable
{

    private array $data;

    private function __construct(array $data) {
        $this->data = $data;
    }

    public static function from(array $data): LogOutput
    {
        return new LogOutput($data);
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
                $array[$key] = $this->recursivelyStringifyObjects($value);
            }
        }
        return $array;
    }
}
