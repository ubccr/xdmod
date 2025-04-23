<?php

namespace DataWarehouse\Data;

use DataWarehouse\Query\Query;
use DateInvalidTimeZoneException;
use DateMalformedStringException;
use XDUser;

/**
 *
 */
class RawDataset
{
    private Query $query;
    private ?array $queryResults;
    private int $userType;

    /**
     * @param $query
     * @param XDUser $user
     */
    public function __construct(&$query, XDUser $user)
    {
        $this->query = $query;
        $this->queryResults = null;
        $this->userType = $user->getUserType();
    }

    /**
     * @param $timestamp
     * @param $timezone
     * @return string
     * @throws DateInvalidTimeZoneException
     * @throws DateMalformedStringException
     */
    private function formattime($timestamp, $timezone): string
    {
        $dateTime = new \DateTime(null, new \DateTimeZone('UTC'));
        $dateTime->setTimestamp($timestamp);
        $dateTime->setTimezone(new \DateTimeZone($timezone));

        return $dateTime->format('Y-m-d\TH:i:s T');
    }

    private function fetchResults()
    {
        if ($this->queryResults === null) {
            $stmt = $this->query->getRawStatement();
            $this->queryResults = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        }
    }

    public function hasResults()
    {
        $this->fetchResults();
        return count($this->queryResults) > 0;
    }

    public function getResults()
    {
        $this->fetchResults();
        return $this->queryResults;
    }

    public function export()
    {
        $this->fetchResults();

        if (count($this->queryResults) == 0) {
            return array();
        }

        $docs = $this->query->getColumnDocumentation();

        $output = array();

        $redactlist = array();

        foreach ($this->queryResults[0] as $key => $value) {

            // Skip the error columns (if present)
            if (\xd_utilities\string_ends_with($key, '_error')) {
                continue;
            }

            $errorMsg = '';

            if (isset($docs[$key])) {
                if (isset($docs[$key]['units'])) {
                    $units = $docs[$key]['units'];
                } else {
                    $units = null;
                }
                $kdoc = $docs[$key]['documentation'];
                $hname = $docs[$key]['name'];
                $group = isset($docs[$key]['group']) ? $docs[$key]['group'] : 'Other';
            } else {
                $units = 'TODO';
                $kdoc =  'TODO';
                $hname =  $key;
                $group = 'Other';
            }

            if ($value === null) {
                if (isset($this->queryResults[0][$key.'_error'])) {
                    $errorMsg = $this->queryResults[0][$key.'_error'];
                } else {
                    continue;
                }
            }

            if ($units == 'ts') {
                if (isset($this->queryResults[0]['timezone'])) {
                    $value = $this->formattime($value, $this->queryResults[0]['timezone']);
                    $units = '';
                } elseif (isset($this->queryResults[0]['Timezone'])) {
                    $value = $this->formattime($value, $this->queryResults[0]['Timezone']);
                    $units = '';
                }
            }

            if ($this->userType == DEMO_USER_TYPE && isset($docs[$key]['visibility']) && $docs[$key]['visibility'] == 'non-public') {
                $redactlist[] = $value;
                $value = "&lt;REDACTED&gt;";
            }

            $output[] = array(
                'key' => $hname,
                'value' => $value,
                'error' => $errorMsg,
                'units' => $units,
                'group' => $group,
                'documentation' => $kdoc
            );
        }

        if (count($redactlist) > 0) {
            foreach ($output as &$datum) {
                foreach ($redactlist as $redact) {
                    if (false !== strpos($datum['value'], $redact)) {
                        $datum['value'] = '&lt;REDACTED&gt;';
                        break;
                    }
                }
            }
        }

        return $output;
    }
}
