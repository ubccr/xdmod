<?php
/**
 * Report schedule manager.
 *
 * Builds and emails reports from the Report Generator.  This script
 * should be run once a day.
 *
 * For testing purposes, the "-m" flag may be specified along with the
 * name of the user to test.  In this situation, only reports for that
 * user will be emailed.
 *
 * The delivery schedule is defined as:
 *
 * - Daily: Always sent.
 * - Weekly: Sent if the current day of the week is Sunday.
 * - Monthly: Sent if it is the third of the month.
 * - Quarterly: Sent if it is the third of the month AND the month
 *       is one of the following: January, April, July, October.
 * - Semi-annually: Sent if it is the third of the month AND the
 *       month is January or July.
 * - Annually: Sent if it is the third of January.
 *
 * NOTE: The monthly, quarterly, semi-annually and annually sent reports
 * are now sent on the third of the month.
 *
 * @author Ryan Gentner
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 * @author Joseph White
 */

require_once __DIR__ . '/../configuration/linker.php';

use CCR\Log;

// Maintenance mode for testing.
$maint_mode = false;

// The name of the user used during maintenance mode.
$maint_user = '';

// Must have no arguments or exactly 2 arguments.
if ($argc != 1 && $argc != 3) {
    fwrite(STDERR, 'Invalid arguments: ' . implode(' ', $argv) . "\n");
    exit(1);
}

if ($argc == 3) {
    if ($argv[1] == '-m') {
        $maint_mode = true;
        $maint_user = $argv[2];
    } else {
        fwrite(STDERR, 'Invalid arguments: ' . implode(' ', $argv) . "\n");
        exit(1);
    }
}

$conf = array(
    'file'         => false,
    'emailSubject' => 'Report Scheduler',
);

$conf['emailSubject'] .= APPLICATION_ENV == 'dev' ? ' [Dev]' : '';

$logger = Log::factory('ReportScheduler', $conf);

// =====================================================================

// NOTE: "process_start_time" is needed for log summary.
$logger->notice('Report scheduler start', ['process_start_time' => date('Y-m-d H:i:s')]);

$active_frequencies = getActiveFrequencies(true);

foreach ($active_frequencies as $frequency) {
    $report_details = XDReportManager::enumScheduledReports($frequency);

    $suffix
        = count($report_details) == 0
        ? 'None'
        : count($report_details);

    $logger->info("Reports Scheduled for $frequency Delivery: $suffix");

    foreach ($report_details as $details) {

        try {
            $user = XDUser::getUserByID($details['user_id']);
        } catch (Exception $e) {
            $msg = "Failed to get user for id = {$details['user_id']}: "
                . $e->getMessage();
            $logger->error($msg, ['stacktrace' => $e->getTraceAsString()]);
            continue;
        }

        if (!$maint_mode || $user->getUsername() == $maint_user) {
            $logger->info(
                "Preparing report {$details['report_id']}"
                . " ({$user->getUsername()})"
            );

            $rm = new XDReportManager($user);

            try {
                $build_response = $rm->buildReport($details['report_id'], null);

                $working_dir     = $build_response['template_path'];
                $report_filename = $build_response['report_file'];

                $mailStatus = $rm->mailReport(
                    $details['report_id'],
                    $report_filename,
                    $frequency,
                    $build_response
                );

            } catch(Exception $e) {
                $msg = "Error Preparing report on " . gethostname() . " {$details['report_id']}: "
                    . $e->getMessage();
                $logger->error($msg, ['stacktrace' => $e->getTraceAsString()]);
            }

            if (isset($working_dir) && $working_dir != '/' && $working_dir != getcwd()) {
                exec("rm -rf $working_dir");
            }
        }
    }
}

// NOTE: "process_end_time" is needed for log summary.
$logger->notice('Report scheduler end', ['process_end_time' => date('Y-m-d H:i:s')]);

exit;

function getActiveFrequencies($verbose = false)
{
    global $logger;

    // See http://php.net/manual/en/function.date.php
    //
    // date (l) -- A full textual representation of the day of the week
    //             ('Sunday' through 'Saturday').
    // date (w) -- Numeric representation of the day of the week
    //             (0 (for Sunday) through 6 (for Saturday)).
    // date (n) -- Numeric representation of a month, without leading
    //             zeros (1 through 12).
    // date (j) -- Day of the month without leading zeros (1 to 31)
    // date (Y) -- A full numeric representation of a year, 4 digits
    //             (Examples: 1999 or 2003).
    $time = date('l w n j Y');

    list(
        $formal_day_of_week,
        $day_of_week,
        $month_index,
        $day_of_month,
        $year
    ) = explode(' ', $time);

    if ($verbose) {
        $logger->info('Current Date');
        $logger->info("Year:         $year");
        $logger->info("Month:        $month_index");
        $logger->info("Day Of Month: $day_of_month");
        $logger->info("Day Of Week:  $day_of_week ($formal_day_of_week)");
    }

    $activeFrequencies = array();

    // Daily (always active)
    $activeFrequencies[] = 'Daily';

    // Weekly (0 = Sunday)
    if ($day_of_week == 0) {
        $activeFrequencies[] = 'Weekly';
    }

    // Monthly (First of the month)
    if ($day_of_month == 3) {
        $activeFrequencies[] = 'Monthly';
    }

    // Quarterly (1 = January, 4 = April, 7 = July, 10 = October)
    $quarter_start_months = array(1, 4, 7, 10);

    // First of the month and the month denotes the start of a quarter
    if ($day_of_month == 3 && in_array($month_index, $quarter_start_months)) {
        $activeFrequencies[] = 'Quarterly';
    }

    // Semi-annually (1 = January, 7 = July)
    $semi_annual_start_months = array(1, 7);

    // First of the month and the month denotes the start of a new 6-month
    // block
    if (
        $day_of_month == 3
        && in_array($month_index, $semi_annual_start_months)
    ) {
        $activeFrequencies[] = 'Semi-annually';
    }

    // Annually (January 3rd)
    if ($month_index == 1 && $day_of_month == 3) {
        $activeFrequencies[] = 'Annually';
    }

    return $activeFrequencies;
}
