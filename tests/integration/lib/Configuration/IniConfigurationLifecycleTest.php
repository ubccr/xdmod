<?php

namespace IntegrationTests\Configuration;

use ArrayObject;
use CCR\Configuration\IniConfiguration;
use CCR\Kernel;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Filesystem\Filesystem;

// We explicitely want to declare more than one class in this file because in addition to the PHPUnit TestCase, we want
// to use a custom Symfony Kernel class that will be wired up in a way that enables us to conduct our tests and will
// never be used by code outside of this file.
//
// phpcs:ignoreFile PSR1.Classes.ClassDeclaration.MultipleClasses

/**
 * This class is meant to document the lifecycle of objects used in Symfony's Depdency Injection. It is important to
 * note, that the behavior we are actually testing is the kernel/container lifecycle, and while this is analgous to the
 * way standard PHP-FPM requests are handled ( e.g. one Kernel per request ), this may not be the case for a persistant
 * server that re-uses it's kernel across requests ( an example being FrankenPHP in worker mode). But at this time,
 * XDMoD does not use a persistent server so the test methodology used below suffices for our needs.
 *
 * The way these tests are setup is, foreach test the following occurs:
 * - A temporary directory is created that will be used to store the test configuration files and as the base directory
 *   for the custom Symfony Kernel.
 * - This temporary directory will initially contain:
 *   - a portal_settings.ini file
 *   - a portal_settings.d directory
 *   - a portal_settings.d/module.ini file
 * - a custom Symfony Kernel class, `IniConfigurationLifecycleKernel` is instantiated and made available as a member
 * variable for the the test to use.
 *
 * Note: The `cache` and `logs` directories are only created after an instance of `IniConfigurationLifecycleKernel` is
 *       booted.
 *
 */
class IniConfigurationLifecycleTest extends TestCase
{
    private string $directory;
    private IniConfigurationLifecycleKernel $kernel;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/xdmod-ini-lifecycle-' . bin2hex(random_bytes(8));
        mkdir($this->directory, 0700);
        mkdir($this->directory . '/portal_settings.d', 0700);
        $this->writeConfiguration('Original', 'Original partial');
        $this->kernel = new IniConfigurationLifecycleKernel($this->directory);
    }

    protected function tearDown(): void
    {
        try {
            if (isset($this->kernel)) {
                $this->kernel->shutdown();
            }
        } finally {
            (new Filesystem())->remove($this->directory);
        }
    }

    private function writeConfiguration(string $title, string $module): void
    {
        file_put_contents($this->directory . '/portal_settings.ini', "[general]\ntitle = \"$title\"\n");
        file_put_contents($this->directory . '/portal_settings.d/module.ini', "[module]\nname = \"$module\"\n");
    }

    private function configurationFrom(string $consumer): IniConfiguration
    {
        return $this->kernel->getContainer()->get($consumer)['configuration'];
    }

    /**
     * Verify independently instantiated consumers receive the same shared service
     * and retain its snapshot after file edits within a single kernel lifecycle.
     *
     * Or another way to put it is that IniConfiguration service is lazy loaded *once*, and after that ( for the
     * lifetime of that request / kernel) all further references to that service will be to that one instance.
     */
    public function testConsumersShareOneConfigurationWithinAKernel(): void
    {
        $this->kernel->boot();
        $first = $this->configurationFrom('test.ini_consumer.first');
        $this->writeConfiguration('Updated', 'Updated partial');
        $second = $this->configurationFrom('test.ini_consumer.second');

        $this->assertSame($first, $second);
        $this->assertSame('Original', $second->getConfigurationOption('general', 'title'));
        $this->assertSame('Original partial', $second->getConfigurationOption('module', 'name'));
    }

    /**
     * Verify rebooting the kernel creates a new service that reads changed base
     * and partial files, even when reusing the already compiled container cache.
     * This models separate PHP-FPM requests rather than persistent-kernel requests.
     */
    public function testKernelRebootLoadsUpdatedConfiguration(): void
    {
        $this->kernel->boot();
        $original = $this->configurationFrom('test.ini_consumer.first');
        $this->assertSame('Original', $original->getConfigurationOption('general', 'title'));
        $this->assertSame('Original partial', $original->getConfigurationOption('module', 'name'));

        $this->kernel->shutdown();
        $this->writeConfiguration('Updated', 'Updated partial');
        $this->kernel->boot();
        $updated = $this->configurationFrom('test.ini_consumer.first');

        $this->assertNotSame($original, $updated);
        $this->assertSame('Updated', $updated->getConfigurationOption('general', 'title'));
        $this->assertSame('Updated partial', $updated->getConfigurationOption('module', 'name'));
        $this->assertSame('Original', $original->getConfigurationOption('general', 'title'));
    }
}

/**
 * This class allows us to test the lifecycle of services registered for dependency injection with Symfony without
 * modifying existing code / configuration parametrs.
 *
 * The bulk of the setup work is done in the `build` function below, such as specifying that when an IniConfiguration
 * class is instantiated, it's `$iniFilePath` parameter should be set to the portal_settings.ini file located in the
 * temporary directory and registering two bare bones consumers, that stand in for full fledged Symfony Services, each
 * consumer having a public `configuration` parameter that is of the `IniConfiguration` type.
 *
 * In the tests, when these consumers are retrieved, Symfony will note that their type is `IniConfiguration` and use
 * the "definition" for `IniConfiguration` to instantiate them.
 *
 */
class IniConfigurationLifecycleKernel extends Kernel
{
    public function __construct(private string $fixtureDirectory)
    {
        # Note that we specify `prod` as the environment to ensure that we're testing "production" behavior.
        parent::__construct('prod', false);
    }

    /**
     * Required to satisfy the Kernel interface.
     * @return string
     */
    public function getCacheDir(): string
    {
        return $this->fixtureDirectory . '/cache';
    }

    /**
     * Required to satisfy the Kernel interface.
     * @return string
     */
    public function getLogDir(): string
    {
        return $this->fixtureDirectory . '/logs';
    }

    /**
     * This is where the magic happens and our custom Kernel is setup to provide the test bed we require.
     *
     * @param ContainerBuilder $container
     * @return void
     */
    protected function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $container->addCompilerPass(
            new class($this->fixtureDirectory) implements CompilerPassInterface {
                public function __construct(private string $directory)
                {
                }

                public function process(ContainerBuilder $container): void
                {
                    $container->setParameter('log_dir', $this->directory . '/logs');
                    // Preserve the application's service definition, including sharing
                    // and logger wiring; override only the path used by these tests.
                    $container->getDefinition(IniConfiguration::class)
                        ->setArgument('$iniFilePath', $this->directory . '/portal_settings.ini');
                    foreach (['first', 'second'] as $name) {
                        $container->register('test.ini_consumer.' . $name, ArrayObject::class)
                            ->setArguments([['configuration' => new Reference(IniConfiguration::class)]])
                            ->setPublic(true);
                    }
                }
            }
        );
    }
}
