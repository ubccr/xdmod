<?php
/**
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace OpenXdmod\Build;

use Exception;
use Xdmod\Template;

/**
 * Build template file helper class.
 *
 * Used generating Open XDMoD install scripts.
 */
class BuildTemplate extends Template
{

    /**
     * Get the template directory path.
     *
     * @return string
     */
    public static function getTemplateDir()
    {
        return BASE_DIR . '/open_xdmod/build_scripts/templates';
    }
}
