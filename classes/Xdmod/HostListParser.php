<?php
/**
 * Host list parsing.  Based on the Hostlist library
 * https://www.nsc.liu.se/~kent/python-hostlist/.
 *
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace Xdmod;

use Exception;

/**
 * Helper class for parsing compressed host lists.
 */
class HostListParser
{

    /**
     * Maximum number of hosts that may be in the expanded host list.
     *
     * Used to prevent expanding excessively large host lists.
     *
     * @var int
     */
    const MAX_SIZE = 65536;

    /**
     * Logger object.
     *
     * @var \Log
     */
    private $logger;

    /**
     * The host list currently being parsed.
     *
     * @var string
     */
    private $hostList;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->logger = \Log::singleton('null');
    }

    /**
     * Set the logger.
     *
     * @param Logger $logger The logger instance.
     */
    public function setLogger(\Log $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Expand a host list string.
     *
     * @param string $hostList Host list in compressed format.
     *
     * @return array Expanded host list.
     */
    public function expandHostList($hostList)
    {
        $this->logger->debug("Expanding host list '$hostList'");

        // If the host list does not contain brackets it is sufficient
        // to split the host list string on commas.
        $hostListContainsBrackets
            = strpos($hostList, '[') !== false
            || strpos($hostList, ']') !== false;

        if (!$hostListContainsBrackets) {
            return explode(',', $hostList);
        }

        // Copy list string to use in error messages.
        $this->hostList = $hostList;

        // Append comma to simplify logic inside loop.
        $hostList .= ',';

        // Hosts that have been parsed.
        $hosts = array();

        // Current bracket nesting level.
        $bracketLevel = 0;

        // Current part being parsed.
        $part = '';

        for ($i = 0; $i < strlen($hostList); ++$i) {
            $c = substr($hostList, $i, 1);

            if ($c === ',' && $bracketLevel === 0) {
                if ($part !== '') {
                    $hosts = array_merge($hosts, $this->expandPart($part));
                }

                $part = '';
            } else {
                $part .= $c;
            }

            if ($c === '[') {
                $bracketLevel += 1;
            } elseif ($c === ']') {
                $bracketLevel -= 1;
            }

            if ($bracketLevel > 1) {
                $msg = "Nested brackets in host list '$this->hostList'";
                throw new Exception($msg);
            }

            if ($bracketLevel < 0) {
                $msg = "Unbalanced brackets in host list '$this->hostList'";
                throw new Exception($msg);
            }
        }

        if ($bracketLevel > 0) {
            $msg = "Unbalanced brackets in host list '$this->hostList'";
            throw new Exception($msg);
        }

        return $hosts;
    }

    /**
     * Helper function for expanding host lists.
     *
     * @param string $part A single part of a host list.
     *
     * @return array Expanded host list.
     */
    private function expandPart($part)
    {
        $this->logger->debug("Expanding host list part '$part'");

        // Base case for recursive expansion.
        if ($part === '') {
            return array('');
        }

        if (!preg_match('/^([^,\[]*)(\[[^\]]*\])?(.*)$/', $part, $matches)) {
            throw new Exception("Bad host list '$part'");
        }

        list(, $prefix, $rangeList, $rest) = $matches;

        $restExpanded = $this->expandPart($rest);

        if ($rangeList === '') {
            $expanded = array($prefix);
        } else {
            // Remove "[" and "]".
            $rangeList = substr($rangeList, 1, -1);

            $expanded = $this->expandRangeList($prefix, $rangeList);
        }

        if (count($expanded) * count($restExpanded) > self::MAX_SIZE) {
            throw new Exception('Results too large');
        }

        return $this->crossProduct($expanded, $restExpanded);
    }

    /**
     * Expand a list of ranges.
     *
     * @param string $prefix The host name prefix.
     * @param string $rangeList List of ranges concatenated with commas.
     *
     * @return array The expanded host list.
     */
    private function expandRangeList($prefix, $rangeList)
    {
        $this->logger->debug("Expanding range list '$rangeList'");

        $results = array();

        foreach (explode(',', $rangeList) as $range) {
            $results = array_merge(
                $results,
                $this->expandRange($prefix, $range)
            );
        }

        return $results;
    }

    /**
     * Expand a single range.
     *
     * @param string $prefix The host name prefix.
     * @param string $range The host range.
     *
     * @return array The expanded host list.
     *
     * @throws Exception
     */
    private function expandRange($prefix, $range)
    {
        $this->logger->debug("Expanding range '$range'");

        // Check for a single number.
        if (preg_match('/^[0-9]+$/', $range)) {
            return array($prefix . $range);
        }

        // Split low-high.
        if (!preg_match('/^([0-9]+)-([0-9]+)$/', $range, $matches)) {
            throw new Exception("Bad range '$range'");
        }

        list(, $low, $high) = $matches;

        $width = strlen($low);

        // Convert to integers to remove any leading zeros.
        $low  = intval($low);
        $high = intval($high);

        if ($high < $low) {
            throw new Exception("Bad range in host list '$range'");
        }

        if ($high - $low > self::MAX_SIZE) {
            throw new Exception('Results too large');
        }

        $results = array();

        // Use the width for consistent padding.
        $format = "%s%0{$width}d";

        foreach (range($low, $high) as $i) {
            $results[] = sprintf($format, $prefix, $i);
        }

        return $results;
    }

    /**
     * Construct the cross product of two arrays by concatenating the
     * elements of each array.
     *
     * @param array $arr1 The first array.
     * @param array $arr2 The second array.
     *
     * @return array
     */
    private function crossProduct(array $arr1, array $arr2)
    {
        $product = array();

        foreach ($arr1 as $e1) {
            foreach ($arr2 as $e2) {
                $product[] = $e1 . $e2;
            }
        }

        return $product;
    }
}
