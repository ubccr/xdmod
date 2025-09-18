<?php
declare(strict_types=1);

use Access\Kernel;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

# These are only here temporarily,
# put together which headers / values are set based on config options.
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: *");
header("Allow: *");

// Configurable constants ---------------------------
$orgConfig = \Configuration\XdmodConfiguration::assocArrayFactory(
    'organization.json',
    CONFIG_DIR
);
// orgConfig is returned as array(0=>array('name' => '', 'abbrev' => ''))
$org = array_shift($orgConfig);
define('ORGANIZATION_NAME', $org['name']);
$org_abbrev = $org['abbrev'];
if (empty($org_abbrev)) {
    $org_abbrev = ORGANIZATION_NAME;
};
define('ORGANIZATION_NAME_ABBREV', $org_abbrev);

$hierarchy = \Configuration\XdmodConfiguration::assocArrayFactory(
    'hierarchy.json',
    CONFIG_DIR
);
define('HIERARCHY_TOP_LEVEL_LABEL', $hierarchy['top_level_label']);
define('HIERARCHY_TOP_LEVEL_INFO', $hierarchy['top_level_info']);
define('HIERARCHY_MIDDLE_LEVEL_LABEL', $hierarchy['middle_level_label']);
define('HIERARCHY_MIDDLE_LEVEL_INFO', $hierarchy['middle_level_info']);
define('HIERARCHY_BOTTOM_LEVEL_LABEL', $hierarchy['bottom_level_label']);
define('HIERARCHY_BOTTOM_LEVEL_INFO', $hierarchy['bottom_level_info']);

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
