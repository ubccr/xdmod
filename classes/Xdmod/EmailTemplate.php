<?php
/**
 * Email template file helper class.
 *
 * Used generating XDMoD emails.
 *
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace Xdmod;

use Exception;

class EmailTemplate extends Template
{

    /**
     * Get the template directory path.
     *
     * @return string
     */
    public static function getTemplateDir()
    {
        return EMAIL_TEMPLATE_DIR;
    }
}
