<?php

namespace User\Elements;

class RequestDescripter
{

    private $_request;
    private $_ignore_keys;
    private $_request_keys;

    /**
     * @param array $ignore_keys An array of elements to ignore when
     *                           building the chart identifier
     */
    public function __construct(
        array $request = array(),
        array $ignore_keys = array(
            "_dc",
            "scale",
            "show_title",
            "show_gradient",
            "width",
            "height"
        )
    ) {
        $this->_request     = $request;
        $this->_ignore_keys = $ignore_keys;

        $this->_request_keys = array_diff(
            array_keys($this->_request),
            $this->_ignore_keys
        );

        sort($this->_request_keys);
    }

    public function equals(array &$request2)
    {
        $req2_keys = array_diff(array_keys($request2), $this->_ignore_keys);

        if (count($this->_request_keys) !== count($req2_keys)) {
            return false;
        }

        foreach ($this->_request_keys as $key) {
            if (
                !isset($request2[$key])
                || $this->_request[$key] !== $request2[$key]
            ) {
                return false;
            }
        }

        return true;
    }

    public function __toString()
    {
        $kvs = array();

        foreach ($this->_request_keys as $key) {
            if ($key != "controller_module") {

                // Array values are not being handled properly, but if
                // they are implicitly converted to a string a warning
                // is generated.  Use the literal string "Array" instead
                // until better serialization is implemented.
                if (is_array($this->_request[$key])) {
                    $kvs[] = $key . '=Array';
                } else {
                    $kvs[] = $key . '=' . $this->_request[$key];
                }
            }
        }

        $identifier = implode('&', $kvs);

        if (isset($this->_request['controller_module'])) {
            $identifier
                = 'controller_module=' . $this->_request['controller_module']
                . '&' . $identifier;
        }

        return $identifier;
    }

    public static function fromString($s)
    {
        $arr = explode('&', $s);

        $ret = array();

        foreach ($arr as $a) {

            // To account for the input string containing a trailing '&'
            if (empty($a)) { continue; }

            list($key, $value) = explode('=', $a, 2);

            if (strlen($key) > 2 && substr($key, -2) === '[]') {
                $key = substr($key, 0, -2);
                if (!isset($ret[$key])) {
                    $ret[$key] = array();
                }
                $ret[$key][] = $value;
            } else {
                $ret[$key] = $value;
            }
        }

        return $ret;
    }
}
