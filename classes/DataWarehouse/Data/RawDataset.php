<?php

namespace DataWarehouse\Data;

class RawDataset
{
    protected $userType;

    private $query;
    private $query_results;

    public function __construct(&$query, $user)
    {
        $this->query = $query;
        $this->query_results = null;
        $this->userType = $user->getUserType();
    }

    private function formattime($timestamp, $timezone)
    {
        $tmpdate = new \DateTime(null, new \DateTimeZone('UTC'));
        $tmpdate->setTimestamp($timestamp);
        $tmpdate->setTimezone(new \DateTimeZone($timezone));

        return $tmpdate->format("Y-m-d\TH:i:s T");
    }

    private function fetchResults()
    {
        if ($this->query_results === null) {
            $stmt = $this->query->getRawStatement();
            $this->query_results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        }
    }

    public function hasResults()
    {
        $this->fetchResults();
        return count($this->query_results) > 0;
    }

    public function getResults()
    {
        $this->fetchResults();
        return $this->query_results;
    }

    public function export()
    {
        $this->fetchResults();

        if (count($this->query_results) == 0) {
            return array();
        }

        $docs = $this->query->getColumnDocumentation();

        $output = array();

        $redactlist = array();

        foreach ($this->query_results[0] as $key => $value) {

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
                if (isset($this->query_results[0][$key.'_error'])) {
                    $errorMsg = $this->query_results[0][$key.'_error'];
                } else {
                    continue;
                }
            }

            if ($units == 'ts') {
                if (isset($this->query_results[0]['timezone'])) {
                    $value = $this->formattime($value, $this->query_results[0]['timezone']);
                    $units = '';
                } elseif (isset($this->query_results[0]['Timezone'])) {
                    $value = $this->formattime($value, $this->query_results[0]['Timezone']);
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
