<?php

/**
 * About tab's Roadmap content.
 *
 * PHP Version 5
 *
 * @category Content
 * @package  CCR.About.Roadmap
 * @author   Ryan Rathsam <ryanrath@buffalo.edu>
 * @license  https://opensource.org/licenses/LGPL-3.0 LGPL-3.0
 */

require_once __DIR__ . '/../../configuration/linker.php';

/**
 * Attempt to retrieve a value from the configuration located at
 * $section->$key.
 *
 * @param str   $section the section in which the desired value resides.
 * @param str   $key     the key under which the desired value can be found.
 * @param mixed $default the default value to provide if there is nothing found.
 *
 * @return mixed
 **/
function getConfigValue($section, $key, $default=null)
{
    try {
        $result = xd_utilities\getConfiguration($section, $key);
    } catch(\Exception $e) {
        $result = $default;
    }
    return $result;
}

$result = array();

$url = getConfigValue('roadmap', 'url');
$header = getConfigValue('roadmap', 'header', '');

if (!empty($header)) {
    $result[]="<p>$header</p>";
}

if (!empty($url)) {
    $result[]="<iframe id='about_roadmap' src='$url' />";
} else {
    $result[] = <<<EOT
      <div class='outer-center'>
        <div class='inner-center'>
          <h1>Roadmap Not Configured</h1>
          <h4>
            Please contact your Systems Administrator if you believe this is
            in error.
          </h4>
        </div>
      </div>
EOT;
}
echo implode($result);
