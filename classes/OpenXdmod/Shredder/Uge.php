<?php
/**
 * Univa Grid Engine shredder.
 *
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace OpenXdmod\Shredder;

use Exception;

class Uge extends Sge
{

    /**
     * These fields are milliseconds in UGE.
     */
    protected $timestampFields = array(
        'submission_time',
        'start_time',
        'end_time',
    );

    /**
     * @inheritdoc
     *
     * TODO: Refactor Sge class to not require the copy/paste below.
     */
    public function shredLine($line)
    {
        $this->logger->debug("Shredding line '$line'");

        // Ignore comments.
        if (substr($line, 0, 1) == '#') {
            return;
        }

        // Ignore lines that contain one character or are blank.
        if (strlen($line) <= 1) {
            return;
        }

        $entries = explode(':', $line);

        // Make sure the number of entries in the input is as great as
        // the number of entry names.  It may be larger due to new
        // fields that have been added to the accounting log format
        // (e.g. "job_class").
        if (count($entries) < self::$minimumEntryCount) {
            $this->logger->err("Malformed UGE acct line: '$line'");
            return;
        }

        $job = array();

        // Map numeric $entries array into a associative array.
        foreach (self::$entryNames as $index => $name) {
           $job[$name] = $entries[$index];
        }

        $this->logger->debug('Parsed data: ' . json_encode($job));

        $job = array_merge(
            $job,
            $this->getResourceLists($job, $job['category'])
        );

        $job['clustername'] = $this->getResource();

        if (!$this->hasResource()) {
            throw new Exception('Resource name required');
        }

        // Convert from milliseconds to seconds.
        foreach ($this->timestampFields as $field) {
            $job[$field] = (int)floor($job[$field] / 1000);
        }

        $this->checkJobData($line, $job);

        $this->insertRow($job);
    }
}
