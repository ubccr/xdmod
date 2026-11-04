<?php

namespace UnitTests\Configuration;

use CCR\Configuration\IniConfiguration;
use PHPUnit\Framework\TestCase;
use Exception;
use Psr\Log\NullLogger;

/**
 * This class is responsible for testing that the functionality of the IniConfiguration class is working as expected,
 * and that the functionality of `IniConfiguration::getConfigurationOption` exactly matches
 * `xd_utilities\getConfiguration` and `IniConfiguration::getConfigurationSection` exactly matches
 * `xd_utilities\getConfigurationSection`.
 */
class IniConfigurationTest extends TestCase
{
    /**
     * The directory in which all temporary files will be created / stored. Cleaned up after each test.
     */
    private string $directory;

    /**
     * The temporary file each test will use.
     */
    private string $iniFile;

    /**
     * Legacy snapshots paired with each instance, including instances predating edits.
     */
    private \SplObjectStorage $legacySnapshots;

    /**
     * Create and seed initial temporary file.
     *
     * @return void
     * @throws \Random\RandomException
     */
    protected function setUp(): void
    {
        $this->legacySnapshots = new \SplObjectStorage();
        // Keep fixtures independent of installed configuration and concurrent runs.
        $this->directory = sys_get_temp_dir() . '/xdmod-ini-' . bin2hex(random_bytes(8));
        mkdir($this->directory, 0700);
        $this->iniFile = $this->directory . '/settings.ini';
        file_put_contents($this->iniFile, "[general]\ntitle = Original\nretained = Keep\n");
    }

    /**
     * Make sure to cleanup all temporary files / directories.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        // Restore permissions first so failed unreadable-file tests also clean up.
        foreach (glob($this->directory . '/settings.d/*') ?: [] as $file) {
            chmod($file, 0600);
            unlink($file);
        }
        if (is_dir($this->directory . '/settings.d')) {
            rmdir($this->directory . '/settings.d');
        }
        foreach (glob($this->directory . '/*') ?: [] as $file) {
            chmod($file, 0600);
            unlink($file);
        }
        rmdir($this->directory);
    }

    /**
     * Load each fixture ( IniConfiguration, and xd_utilities\loadConfiguration ), since the path used in the original
     * configuration code is a constant, a child process is used to set it without touching installed files
     * or redefining constants in PHPUnit. Once constructed, we retain its snapshot so we can use it to test against.
     */
    private function createConfiguration(): IniConfiguration
    {
        // new Symfony configuration.
        $configuration = new IniConfiguration($this->iniFile, new NullLogger());

        // A script that calls / serializes original configuration code.
        // $argv[1] is the ini file, $argv[2] is the utilities.php file.
        $script = <<<'PHP'
        define('CONFIG_PORTAL_SETTINGS', $argv[1]);
        require $argv[2];
        echo serialize(\xd_utilities\loadConfiguration());
        PHP;

        $pipes = [];
        # exceute the above script, echoing the serialized configuration.
        $process = proc_open(
            [PHP_BINARY, '-r', $script, $this->iniFile, BASE_DIR . '/libraries/utilities.php'],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes
        );
        $this->assertIsResource($process);
        try {
            $output = stream_get_contents($pipes[1]);
            $errors = stream_get_contents($pipes[2]);
        } finally {
            fclose($pipes[1]);
            fclose($pipes[2]);
            $status = proc_close($process);
        }
        $this->assertSame(0, $status, $errors);

        # unserialize the output, so taht we can save it off to compare against for tests.
        $snapshot = unserialize($output, ['allowed_classes' => false]);
        $this->assertIsArray($snapshot);

        # save this so we can compare it to the new configuration.
        $this->legacySnapshots[$configuration] = $snapshot;

        return $configuration;
    }

    /**
     * Helper function that compares IniConfiguration::getConfigurationOption to it's match, `xd_utilities\getConfiguration`.
     */
    private function getMatchingOption(IniConfiguration $configuration, string $section, ?string $option = null): mixed
    {
        return $this->compareLookup(
            $configuration,
            fn () => $configuration->getConfigurationOption($section, $option),
            fn () => \xd_utilities\getConfiguration($section, $option)
        );
    }

    /**
     * Helper function that compares IniConfiguration::getConfigurationSection to it's match `xd_utilities\getConfigurationSection`.
     */
    private function getMatchingSection(IniConfiguration $configuration, string $section): mixed
    {
        return $this->compareLookup(
            $configuration,
            fn () => $configuration->getConfigurationSection($section),
            fn () => \xd_utilities\getConfigurationSection($section)
        );
    }

    /**
     * Helper function that handles the actual comparison of new vs. old code.
     * Supply the real legacy loader's snapshot through its cache, restoring any
     * prior state afterward. Compare exact value types or exception class/message;
     * rethrow only after comparison so existing expectException checks still apply.
     */
    private function compareLookup(IniConfiguration $configuration, callable $modern, callable $legacy): mixed
    {
        $hadCache = array_key_exists('iniData', $GLOBALS);

        # Save off any values in `iniData` so that we can restore them after the test.
        $original = $GLOBALS['iniData'] ?? null;

        # Store the legacy loader's snapshot in $GLOBALS['iniData'] so that it can be used in the `xd_utilities\*`
        # functions.
        $GLOBALS['iniData'] = $this->legacySnapshots[$configuration];
        try {
            $modernValue = $legacyValue = null;
            $modernError = $legacyError = null;
            try {
                $modernValue = $modern();
            } catch (Exception $exception) {
                $modernError = $exception;
            }
            try {
                $legacyValue = $legacy();
            } catch (Exception $exception) {
                $legacyError = $exception;
            }

            # Make sure that if there was an error, that the class and messages match.
            $this->assertSame(
                $legacyError === null ? null : [get_class($legacyError), $legacyError->getMessage()],
                $modernError === null ? null : [get_class($modernError), $modernError->getMessage()]
            );

            # If the symfony code had an error, throw it so it can be expected by the test.
            if ($modernError !== null) {
                throw $modernError;
            }

            # Make sure that the values match.
            $this->assertSame($legacyValue, $modernValue);

            return $modernValue;
        } finally {
            # Reset or clean the global cache of the original configuration data.
            if ($hadCache) {
                $GLOBALS['iniData'] = $original;
            } else {
                unset($GLOBALS['iniData']);
            }
        }
    }

    /**
     * Create a new file in the settings.d directory so that we can test how both sets of code handle those.
     *
     * @param string $name the name of the file to create
     * @param string $contents the content to use in the creation of the file.
     * @return string the path of the file created.
     */
    private function writeOverride(string $name, string $contents): string
    {
        $directory = $this->directory . '/settings.d';
        if (!is_dir($directory)) {
            mkdir($directory, 0700);
        }
        $path = $directory . '/' . $name;
        file_put_contents($path, $contents);
        return $path;
    }

    /**
     * Verify key and section retrieval without a .d directory, so overrides
     * remain optional for ordinary INI files.
     */
    public function testLoadsBaseFileWithoutOverrideDirectory(): void
    {
        $configuration = $this->createConfiguration();

        $this->assertSame('Original', $this->getMatchingOption($configuration, 'general', 'title'));
        $expected = ['title' => 'Original', 'retained' => 'Keep'];
        $this->assertSame($expected, $this->getMatchingSection($configuration, 'general'));
    }

    /**
     * Verify an existing but empty .d directory preserves the base configuration,
     * allowing installations to create the directory before adding module files.
     */
    public function testEmptyOverrideDirectoryLeavesBaseValuesIntact(): void
    {
        mkdir($this->directory . '/settings.d');

        $configuration = $this->createConfiguration();

        $this->assertSame('Original', $this->getMatchingOption($configuration, 'general', 'title'));
        $this->assertSame('Keep', $this->getMatchingOption($configuration, 'general', 'retained'));
    }

    /**
     * Verify a partial file adds a new section without altering unrelated sections,
     * supporting modules that contribute their own configuration namespaces.
     */
    public function testOverrideCanAddASection(): void
    {
        $this->writeOverride('module.ini', "[module]\nurl = https://example.org\n");

        $configuration = $this->createConfiguration();

        $this->assertSame('https://example.org', $this->getMatchingOption($configuration, 'module', 'url'));
        $this->assertSame('Original', $this->getMatchingOption($configuration, 'general', 'title'));
    }

    /**
     * Verify a partial can add a key without discarding existing section values,
     * preserving the legacy merge behavior used by module configuration.
     */
    public function testAddingAKeyInAnOverridePreservesExistingKeys(): void
    {
        $this->writeOverride('module.ini', "[general]\nadded = New\n");

        $configuration = $this->createConfiguration();

        $this->assertSame('New', $this->getMatchingOption($configuration, 'general', 'added'));
        $this->assertSame(
            ['title' => 'Original', 'retained' => 'Keep', 'added' => 'New'],
            $this->getMatchingSection($configuration, 'general')
        );
    }

    /**
     * Verify a partial takes precedence over a base value with the same section
     * and key, allowing local configuration to override shipped defaults.
     */
    public function testOverrideReplacesMatchingKey(): void
    {
        $this->writeOverride('module.ini', "[general]\ntitle = Replaced\n");

        $configuration = $this->createConfiguration();

        $this->assertSame('Replaced', $this->getMatchingOption($configuration, 'general', 'title'));
    }

    /**
     * Verify overriding one key preserves omitted sibling keys, so a partial
     * only needs to specify the settings it intends to change.
     */
    public function testOverridePreservesSiblingKeys(): void
    {
        $this->writeOverride('module.ini', "[general]\ntitle = Replaced\n");

        $configuration = $this->createConfiguration();

        $this->assertSame(
            ['title' => 'Replaced', 'retained' => 'Keep'],
            $this->getMatchingSection($configuration, 'general')
        );
        $this->assertSame('Keep', $this->getMatchingOption($configuration, 'general', 'retained'));
    }

    /**
     * Verify filename order determines precedence even when files are created in
     * reverse order, so numbered module and local overrides behave predictably.
     */
    public function testLaterFilenameWinsRegardlessOfCreationOrder(): void
    {
        $this->writeOverride('20-local.ini', "[general]\ntitle = Local\n");
        $this->writeOverride('10-module.ini', "[general]\ntitle = Module\n");

        $configuration = $this->createConfiguration();

        $this->assertSame('Local', $this->getMatchingOption($configuration, 'general', 'title'));
    }

    /**
     * Verify files without the .ini extension are not parsed, so that other
     * unrelated files in the override directory cannot break configuration loading.
     */
    public function testNonIniFilesAreIgnored(): void
    {
        $this->writeOverride('notes.txt', "This is not valid INI: [\n");

        $configuration = $this->createConfiguration();

        $this->assertSame('Original', $this->getMatchingOption($configuration, 'general', 'title'));
    }

    /**
     * Verify absent sections raise an exception rather than returning fallback
     * values, making missing required configuration visible to callers.
     */
    public function testMissingSectionIsRejected(): void
    {
        $configuration = $this->createConfiguration();
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Undefined configuration section: 'missing'");

        $this->getMatchingSection($configuration, 'missing');
    }

    /**
     * Verify an absent option in an existing section raises an exception naming
     * both the option and section, helping callers identify the missing setting.
     */
    public function testMissingOptionIsRejected(): void
    {
        $configuration = $this->createConfiguration();
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Option 'missing' does not exist in section 'general'");

        $this->getMatchingOption($configuration, 'general', 'missing');
    }

    /**
     * Verify normal INI scanning produces the expected strings and arrays and that
     * empty strings and zero survive lookup without conversion or rejection.
     */
    public function testParsingPreservesStringsArraysAndFalseLikeValues(): void
    {
        file_put_contents($this->iniFile, "[values]\nempty = \"\"\nzero = 0\ndisabled = false\n"
            . "enabled = true\nnull_value = null\nitems[] = first\nitems[] = second\n");

        $configuration = $this->createConfiguration();

        foreach (['empty' => '', 'zero' => '0', 'disabled' => '', 'enabled' => '1', 'null_value' => ''] as $key => $value) {
            $this->assertSame($value, $this->getMatchingOption($configuration, 'values', $key), $key);
        }
        $this->assertSame(['first', 'second'], $this->getMatchingOption($configuration, 'values', 'items'));
    }

    /**
     * Verify a missing base file raises Exception identifying its path,
     * preventing silent startup with missing required configuration.
     */
    public function testMissingBaseFileIsRejected(): void
    {
        unlink($this->iniFile);
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Could not read settings file: ' . $this->iniFile);

        $this->createConfiguration();
    }

    /**
     * Verify malformed base and partial files raise a parsing exception naming
     * the offending path, so invalid overrides are not silently ignored.
     *
     * @dataProvider provideInvalidFiles
     */
    public function testInvalidFilesAreRejected(bool $override, string $contents): void
    {
        if ($override) {
            $path = $this->writeOverride('invalid.ini', $contents);
        } else {
            $path = $this->iniFile;
            file_put_contents($path, $contents);
        }

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Failed to parse settings file: ' . $path);

        // parse_ini_file emits a warning before returning false. Suppress only
        // that expected parser warning so the class's exception can be checked.
        set_error_handler(static function (int $severity, string $message) use ($path): bool {
            return $severity === E_WARNING
                && str_contains($message, 'syntax error')
                && str_contains($message, $path);
        });
        try {
            $this->createConfiguration();
        } finally {
            restore_error_handler();
        }
    }

    public static function provideInvalidFiles(): array
    {
        return [
            'malformed base' => [false, "[unterminated\n"],
            'malformed override' => [true, "[unterminated\n"],
        ];
    }

    /**
     * Verify an empty base file loads successfully but a lookup still rejects
     * an absent section, distinguishing empty configuration from parse failure.
     */
    public function testEmptyBaseFileIsAccepted(): void
    {
        file_put_contents($this->iniFile, '');
        $configuration = $this->createConfiguration();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Undefined configuration section: 'general'");
        $this->getMatchingSection($configuration, 'general');
    }

    /**
     * Verify an empty partial is a harmless no-op, allowing placeholder override
     * files without rejecting or erasing the base configuration.
     */
    public function testEmptyOverrideIsAcceptedWithoutChangingBaseValues(): void
    {
        $this->writeOverride('empty.ini', '');
        $configuration = $this->createConfiguration();

        $this->assertSame(['title' => 'Original', 'retained' => 'Keep'], $this->getMatchingSection($configuration, 'general'));
    }

    /**
     * Verify empty-string and zero-string section names raise the documented
     * Exception, recording the validation imposed by empty().
     *
     * @dataProvider provideEmptySections
     */
    public function testEmptySectionIsRejectedWithException(string $section): void
    {
        $configuration = $this->createConfiguration();
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Configuration section not specified');

        $this->getMatchingOption($configuration, $section);
    }

    public static function provideEmptySections(): array
    {
        return ['empty string' => [''], 'zero string' => ['0']];
    }

    /**
     * Verify omitted, null, empty, and zero options are rejected even if a zero
     * key exists, documenting the legacy-compatible empty() validation.
     *
     * @dataProvider provideEmptyOptions
     */
    public function testEmptyOptionIsRejected(?string $option, bool $omitted): void
    {
        file_put_contents($this->iniFile, "[general]\n0 = Zero\n");
        $configuration = $this->createConfiguration();
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Configuration option not specified');

        if ($omitted) {
            $this->getMatchingOption($configuration, 'general');
        } else {
            $this->getMatchingOption($configuration, 'general', $option);
        }
    }

    public static function provideEmptyOptions(): array
    {
        return [
            'omitted' => [null, true],
            'null' => [null, false],
            'empty string' => ['', false],
            'zero string' => ['0', false],
        ];
    }

    /**
     * Verify both APIs throw the same Exception class and messages for invalid
     * lookups, protecting migration compatibility using the same temporary files.
     *
     * @dataProvider provideLegacyLookupErrors
     */
    public function testLookupErrorsMatchLegacy(string $section, ?string $key, bool $wholeSection, string $message): void
    {
        file_put_contents($this->iniFile, "[general]\ntitle = Original\n0 = Zero\n");
        $configuration = $this->createConfiguration();
        $this->expectException(Exception::class);
        $this->expectExceptionMessage($message);

        if ($wholeSection) {
            $this->getMatchingSection($configuration, $section);
        } else {
            $this->getMatchingOption($configuration, $section, $key);
        }
    }

    public static function provideLegacyLookupErrors(): array
    {
        return [
            'empty section' => ['', null, true, 'Configuration section not specified'],
            'zero section' => ['0', null, true, 'Configuration section not specified'],
            'missing section' => ['missing', null, true, "Undefined configuration section: 'missing'"],
            'missing key' => ['general', 'missing', false, "Option 'missing' does not exist in section 'general'"],
            'empty key' => ['general', '', false, 'Configuration option not specified'],
            'zero key' => ['general', '0', false, 'Configuration option not specified'],
            'null key' => ['general', null, false, 'Configuration option not specified'],
        ];
    }

    /**
     * Verify an existing instance still works after file deletion while constructing
     * another fails immediately, establishing eager loading and snapshot lifetime.
     */
    public function testConstructionLoadsImmediatelyAndExistingInstanceDoesNotReload(): void
    {
        $configuration = $this->createConfiguration();
        unlink($this->iniFile);

        $this->assertSame('Original', $this->getMatchingOption($configuration, 'general', 'title'));
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Could not read settings file: ' . $this->iniFile);
        $this->createConfiguration();
    }

    /**
     * Verify unreadable base and partial files raise an exception naming the path,
     * preventing incomplete configuration from being used after permission errors.
     * Skip when the current user's privileges bypass the fixture's permissions.
     *
     * @dataProvider provideUnreadableFiles
     */
    public function testUnreadableFilesAreRejected(bool $override): void
    {
        $path = $override
            ? $this->writeOverride('unreadable.ini', "[module]\nvalue = test\n")
            : $this->iniFile;
        chmod($path, 0000);
        clearstatcache(true, $path);
        if (is_readable($path)) {
            $this->markTestSkipped('This user can read files despite removed read permissions.');
        }

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Could not read settings file: ' . $path);

        $this->createConfiguration();
    }

    public static function provideUnreadableFiles(): array
    {
        return ['base file' => [false], 'override file' => [true]];
    }

    /**
     * Verify edits affect newly constructed instances while existing instances keep
     * their original values, documenting when requests or workers see file changes.
     * NOTE: this verifies that changes to the file will be picked up on next request as the configuration is created
     * once per request cycle.
     */
    public function testExistingInstanceKeepsSnapshotWhileNewInstanceReadsChanges(): void
    {
        $configuration = $this->createConfiguration();
        file_put_contents($this->iniFile, "[general]\ntitle = Updated\n");

        $this->assertSame('Original', $this->getMatchingOption($configuration, 'general', 'title'));
        $this->assertSame('Updated', $this->getMatchingOption($this->createConfiguration(), 'general', 'title'));
    }
}
