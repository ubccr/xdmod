<?php
/**
 * JSON related functions.
 *
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace CCR;

use Exception;

class Json
{

    /**
     * Read and decode a file containing JSON.
     *
     * @param string $file JSON file path.
     * @param bool $assoc When true, returns an associative array
     *     (default: true).
     *
     * @return array|object
     */
    public static function loadFile($file, $assoc = true)
    {
        $contents = file_get_contents($file);

        if ($contents === false) {
            throw new Exception("Failed to read file '$file'");
        }

        $data = json_decode($contents, $assoc);

        if ($data === null) {
            $msg = "Failed to decode file '$file': "
                . static::getLastErrorMessage()
                . "\nContents = " . $contents;
            throw new Exception($msg);
        }

        return $data;
    }

    /**
     * JSON encode and write data to a file.
     *
     * @param string $file Destination file path.
     * @param array $data Data to encode and write to file.
     */
    public static function saveFile($file, array $data)
    {
        $contents = json_encode($data);

        if ($contents === false) {
            $msg = 'Failed to encode data: ' . static::getLastErrorMessage()
                . "\nData = " . print_r($data, true);
            throw new Exception($msg);
        }

        $contents = static::prettyPrint($contents);

        $byteCount = file_put_contents($file, $contents);

        if ($byteCount === false) {
            throw new Exception("Failed to write file '$file'");
        }
    }

    /**
     * Format a JSON string.
     *
     * Adapted from:
     * http://www.daveperrett.com/articles/2008/03/11/format-json-with-php/
     *
     * @param string $json A valid JSON string.
     *
     * @return string A pretty printed version of the input.
     */
    public static function prettyPrint($json)
    {

        // Decode and then re-encode JSON string to ensure that it is
        // valid and that it does not contain any excess whitespace.
        $data = json_decode($json, true);

        if ($data === null) {
            $msg = 'Invalid JSON: ' . static::getLastErrorMessage()
                . "\nContents = " . $json;
            throw new Exception($msg);
        }

        $json = json_encode($data);

        if ($json === false) {
            $msg = 'Failed to encode data: ' . static::getLastErrorMessage()
                . "\nData = " . print_r($data, true);
            throw new Exception($msg);
        }

        $result      = '';
        $indentLvl   = 0;
        $strLen      = strlen($json);
        $indentStr   = str_repeat(' ', 4);
        $newLine     = "\n";
        $prevChar    = '';
        $outOfQuotes = true;

        for ($i = 0; $i <= $strLen; $i++) {
            // Grab the currect character in the string.
            $char = substr($json, $i, 1);

            // Get the next character for lookahead.
            $nextChar = $i < $strLen ? substr($json, $i + 1, 1) : '';

            // Are we inside a quoted string?
            if ($char == '"' && $prevChar != '\\') {
                $outOfQuotes = !$outOfQuotes;
            } elseif (($char == '}' || $char == ']') && $outOfQuotes) {
                // If this character is the end of an element,
                // output a new line and indent the next line.
                $indentLvl--;
                $result .= $newLine . str_repeat($indentStr, $indentLvl);
            }

            // Add the character to the result string.
            $result .= $char;

            if ($outOfQuotes) {
                // If the last character was the beginning of an
                // element, output a new line and indent the next line.
                if ($char == ',' || $char == '{' || $char == '[') {
                    if ($char == '{' || $char == '[') {
                        $indentLvl++;
                    }

                    // If the array or object is empty, close it immediately
                    // skipping the next char.
                    if (($char     == '{' || $char     == '[')
                        && ($nextChar == '}' || $nextChar == ']')
                    ) {
                        $result .= $nextChar;
                        $indentLvl--;
                        $i++;
                    } else {
                        $result .= $newLine . str_repeat($indentStr, $indentLvl);
                    }
                } elseif ($char == ':') {
                    $result .= ' ';
                }
            }

            $prevChar = $char;
        }

        $result .= $newLine;

        return $result;
    }

    /**
     * Return the error string for the given error code.
     *
     * PHP < 5.5.0 does not have the json_last_error_msg function, this
     * function can be used to return a human readable error message
     * given the returned value of json_last_error.
     *
     * @param int $error Error code from json_last_error.
     *
     * @return string Error message.
     */
    public static function getErrorMessage($error)
    {
        switch ($error) {
            case JSON_ERROR_NONE:
                return 'No error';
                break;
            case JSON_ERROR_DEPTH:
                return 'Maximum stack depth exceeded';
                break;
            case JSON_ERROR_STATE_MISMATCH:
                return 'Invalid or malformed JSON';
                break;
            case JSON_ERROR_CTRL_CHAR:
                return 'Control character error';
                break;
            case JSON_ERROR_SYNTAX:
                return 'Syntax error';
                break;
            default:
                return 'Unknown error';
                break;
        }
    }

    /**
     * Return the error string for the last json_encode or json_decode.
     *
     * PHP < 5.5.0 does not have the json_last_error_msg function, this
     * function can be used in its place.
     *
     * @return string Error message.
     */
    public static function getLastErrorMessage()
    {
        return static::getErrorMessage(json_last_error());
    }
}
