<?php
/**
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace OpenXdmod\Setup;

use Configuration\XdmodConfiguration;

/**
 * Open XDMoD main setup menu.
 */
class MainMenu extends Menu
{

    /**
     * Factory method.
     */
    public static function factory()
    {
        $configFile = new XdmodConfiguration(
            'setup.json',
            CONFIG_DIR
        );
        $configFile->initialize();
        $config = json_decode($configFile->toJson(), true);

        $itemConf = $config['menu'];

        // Sort menu items by relative position.
        usort(
            $itemConf,
            function ($a, $b) {
                return $a['position'] < $b['position'] ? -1 : 1;
            }
        );

        $console = Console::factory();
        $items   = array();
        $count   = 0;

        foreach ($itemConf as $conf) {

            // If there is no trigger in the configuration, then use
            // consecutive numbers.
            $trigger
                = isset($conf['trigger'])
                ? $conf['trigger']
                : ++$count;

            $cls     = __NAMESPACE__ . '\\' .  $conf['handler'];
            $handler = new $cls($console);

            $items[] = new MenuItem($trigger, $conf['label'], $handler);
        }

        return new static($items, $console, 'Open XDMoD Setup');
    }
}
