<?php declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

/**
 * This file / static function will read in the contents of CONFIG_DIR/portal_settings.[ini,d] and create parameters
 * that can be referenced elsewhere in our Symfony code under `xdmod.portal_settings.<section>.<property_name>`.
 *
 * Reference: https://symfony.com/doc/current/configuration.html#accessing-configuration-parameters
 */
return static function (ContainerConfigurator $container): void {
    $portalSettingsData = \xd_utilities\loadConfiguration();
    foreach ($portalSettingsData as $section => $sectionData) {
        foreach ($sectionData as $key => $value) {
            $id = sprintf('xdmod.portal_settings.%s.%s', $section, $key);
            $container->parameters()->set($id, $value);
        }
    }
};
