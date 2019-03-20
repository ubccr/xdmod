<?php
/**
 * Generates HTML tags for module assets.
 */

namespace OpenXdmod;

use Configuration\XdmodConfiguration;

class Assets
{

    /**
     * Assets configuration array.
     *
     * @var array
     */
    private static $assetsConfig = null;

    /**
     * Generate JS and CSS HTML tags.
     *
     * @param string $section Either "portal" or "internal_dashboard".
     *
     * @return string HTML script and link tags.
     */
    public static function generateAssetTags($section)
    {
        $tags = '';

        foreach (static::getAssetsPaths($section, 'css') as $path) {
            $tags .= '<link rel="stylesheet" type="text/css" href="' . $path . '" />' . "\n";
        }

        foreach (static::getAssetsPaths($section, 'js') as $path) {
            $tags .= '<script type="text/javascript" src="' . $path . '"></script>' . "\n";
        }

        return $tags;
    }

    /**
     * Construct a list of unique assets for the given section and type.
     *
     * @param string $section Either "portal" or "internal_dashboard".
     * @param string $type Either "js" or "css".
     *
     * @return array
     */
    private static function getAssetsPaths($section, $type)
    {
        $config = static::getConfiguration();

        $files = array();

        foreach ($config as $module => $sections) {

            if (isset($sections[$section])) {
                $sectionData = $sections[$section];

                if (isset($sectionData[$type])) {
                    foreach ($sectionData[$type] as $file) {

                        // Check for duplicates.  Avoiding use of `array_unique`
                        // since that sorts the array and file order may be
                        // important.
                        if (!in_array($file, $files)) {
                            $files[] = $file;
                        }
                    }
                }
            }
        }

        return $files;
    }

    /**
     * Get the assets configuration object.
     *
     * @return array
     * @throws \Exception
     */
    private static function getConfiguration()
    {
        if (static::$assetsConfig === null) {
            static::$assetsConfig = XdmodConfiguration::assocArrayFactory("assets.json", CONFIG_DIR);
        }

        return static::$assetsConfig;
    }
}
