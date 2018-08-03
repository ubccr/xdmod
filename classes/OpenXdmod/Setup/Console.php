<?php
/**
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace OpenXdmod\Setup;

/**
 * Open XDMoD console user interface.
 */
class Console
{

    /**
     * Console singleton.
     */
    protected static $singleton;

    /**
     * Factory method.
     */
    public static function factory()
    {
        if (!isset(static::$singleton)) {
            static::$singleton = new static();
        }

        return static::$singleton;
    }

    /**
     * Constructor.
     */
    protected function __construct()
    {
    }

    /**
     * Display a message.
     *
     * @param string $message The message that will be displayed.
     */
    public function displayMessage($msg)
    {
        echo $msg, "\n";
    }

    /**
     * Display a blank line.
     */
    public function displayBlankLine()
    {
        $this->displayMessage('');
    }

    /**
     * Display a section header.
     */
    public function displaySectionHeader($headerText)
    {
        $this->clear();
        $this->displayMessage(str_repeat('=', 72));
        $this->displayMessage($headerText);
        $this->displayMessage(str_repeat('=', 72));
        $this->displayBlankLine();
    }

    /**
     * Display a warning.
     *
     * @param  array|string $lines One string or an array of strings to print.
     */
    public function displayWarning($lines)
    {
        if (!is_array($lines)) {
            $lines = array($lines);
        }

        $this->displayMessage(str_repeat('!', 72));
        $this->displayMessage('! ');
        foreach ($lines as $line) {
            $this->displayMessage("! $line");
        }
        $this->displayMessage('! ');
        $this->displayMessage(str_repeat('!', 72));
        $this->displayBlankLine();
    }

    /**
     * Clear the terminal.
     */
    public function clear()
    {
        system('clear');
    }

    /**
     * Prompt the user.
     *
     * @param string $query The text to use as the prompt.
     * @param string $default The default response to use if the user
     *     doesn't enter anything.
     * @param array $options Options to display and constrain the
     *     repsonse from the user.
     *
     * @return string The user's response (or the default option).
     */
    public function prompt($query, $default = '', array $options = array())
    {
        $prompt = $query;

        if (count($options) > 0) {
            if ($default != '' && !in_array(strtolower($default), $options)) {
                throw new \Exception('Default value is not an option');
            }

            $lastChar = substr($prompt, -1, 1);

            if (in_array($lastChar, array(':', '?'))) {
                $prompt = substr($prompt, 0, strlen($prompt) - 1);
            }

            $prompt .= ' (' . implode(', ', $options) . ')';

            if (in_array($lastChar, array(':', '?'))) {
                $prompt .= $lastChar;
            }
        }

        if (!empty($default)) {
            $prompt .= " [$default]";
        }

        $prompt .= ' ';

        $response = readline($prompt);

        if (!empty($default) && $response === '') {
            return $default;
        }

        if (count($options) > 0) {
            $response = strtolower($response);
            if (!in_array($response, $options)) {
                $this->displayBlankLine();
                $this->displayMessage("'$response' is not a valid option.");
                $this->displayBlankLine();
                return $this->prompt($query, $default, $options);
            }
        }

        return $response;
    }

    /**
     * Prompt the user for a boolean response.
     * @param string $query The text to use as the prompt.
     * @param bool $default The default response to use if the user
     *     doesn't enter anything.
     * @return bool The user's response converted to a bool (or the default option).
     */
    public function promptBool($query, $default = true)
    {
        $defaultTxt = $default ? 'yes' : 'no';
        $options = array('yes', 'no');

        return filter_var($this->prompt($query, $defaultTxt, $options), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Prompt the user, but don't display their reponse.
     *
     * Also asks the user to confirm their response.  If the two
     * responses don't match, prompts again.
     *
     * @param string $prompt The text to use as the prompt.
     *
     * @return string The user's response.
     */
    public function silentPrompt($prompt)
    {
        echo "$prompt ";
        $first = preg_replace('/\r?\n$/', '', `stty -echo; head -n1; stty echo`);
        echo "\n(confirm) $prompt ";
        $second = preg_replace('/\r?\n$/', '', `stty -echo; head -n1; stty echo`);
        echo "\n";

        if ($first != $second) {
            $this->displayBlankLine();
            $this->displayMessage('Entries did not match, please try again.');
            $this->displayBlankLine();
            return $this->silentPrompt($prompt);
        }

        return $first;
    }
}
