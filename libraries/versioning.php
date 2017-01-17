<?php
/**
 * Version related functions.
 */

namespace xd_versioning;

/**
 * Get the portal version number.
 *
 * @param bool $short True if the short version number should be
 *     returned (default false).
 *
 * @return string
 */
function getPortalVersion($short = false)
{
    $version = \xd_utilities\getConfiguration('general', 'version');

    if ($short) {
        // Remove any trailing version info.
        $ver = explode(' (', $version);
        return $ver[0];
    }

    // Acquire the version information if possible.
    $revision = exec(
        'git log -1 --pretty=format:"%h" 2>&1',
        $output,
        $returnVar
    );

    // If there was an error executing git don't clear the output.
    if ($returnVar != 0) {
        $revision = '';
    }

    if (!empty($revision)) {
        // This is a development version (since the git meta-data (in
        // the .git directory) is intact).
        $version .= sprintf('.%s (%s) Dev', $revision, date('Y.m.d'));
    }

    return $version;
}
