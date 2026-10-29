<?php

namespace UnitTests\Configuration;

use CCR\Configuration\IniConfiguration;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class IniConfigurationTest extends TestCase
{
    /**
     * The directory in which all temporary files will be created / stored. Cleaned up after each test.
     * @var string
     */
    private string $directory;

    /**
     * The temporary file each test will use.
     *
     * @var string
     */
    private string $iniFile;

    /**
     * Create and seed initial temporary file.
     *
     * @return void
     * @throws \Random\RandomException
     */
    protected function setUp(): void
    {
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
     * Verify key and section retrieval without a .d directory, including omitted
     * and explicit null keys, so overrides remain optional for ordinary INI files.
     */
    public function testLoadsBaseFileWithoutOverrideDirectory(): void
    {
        $configuration = new IniConfiguration($this->iniFile);

        $this->assertSame('Original', $configuration->get('general', 'title'));
        $expected = ['title' => 'Original', 'retained' => 'Keep'];
        $this->assertSame($expected, $configuration->get('general'));
        $this->assertSame($expected, $configuration->get('general', null));
    }

    /**
     * Verify an existing but empty .d directory preserves the base configuration,
     * allowing installations to create the directory before adding module files.
     */
    public function testEmptyOverrideDirectoryLeavesBaseValuesIntact(): void
    {
        mkdir($this->directory . '/settings.d');

        $configuration = new IniConfiguration($this->iniFile);

        $this->assertSame('Original', $configuration->get('general', 'title'));
        $this->assertSame('Keep', $configuration->get('general', 'retained'));
    }

    /**
     * Verify a partial file adds a new section without altering unrelated sections,
     * supporting modules that contribute their own configuration namespaces.
     */
    public function testOverrideCanAddASection(): void
    {
        $this->writeOverride('module.ini', "[module]\nurl = https://example.org\n");

        $configuration = new IniConfiguration($this->iniFile);

        $this->assertSame('https://example.org', $configuration->get('module', 'url'));
        $this->assertSame('Original', $configuration->get('general', 'title'));
    }

    /**
     * NOTE: This is probably not actually desirable, but it's the way the old code works so here it is.
     *
     * Document that adding a key through a partial replaces the entire matching
     * section. This records the current difference from legacy key-by-key merging.
     */
    public function testAddingAKeyInAnOverrideReplacesTheSection(): void
    {
        $this->writeOverride('module.ini', "[general]\nadded = New\n");

        $configuration = new IniConfiguration($this->iniFile);

        $this->assertSame('New', $configuration->get('general', 'added'));
        // Characterize the current section replacement behavior, which differs
        // from the legacy loader's key-by-key merge.
        $this->assertSame(['added' => 'New'], $configuration->get('general'));
        $this->assertNull($configuration->get('general', 'title'));
        $this->assertNull($configuration->get('general', 'retained'));
    }

    /**
     * Verify a partial takes precedence over a base value with the same section
     * and key, allowing local configuration to override shipped defaults.
     */
    public function testOverrideReplacesMatchingKey(): void
    {
        $this->writeOverride('module.ini', "[general]\ntitle = Replaced\n");

        $configuration = new IniConfiguration($this->iniFile);

        $this->assertSame('Replaced', $configuration->get('general', 'title'));
    }

    /**
     * Confirm that overriding a key also removes omitted sibling keys under the
     * current section replacement policy, making this migration difference explicit.
     */
    public function testOverrideDiscardsSiblingKeys(): void
    {
        $this->writeOverride('module.ini', "[general]\ntitle = Replaced\n");

        $configuration = new IniConfiguration($this->iniFile);

        $this->assertSame(['title' => 'Replaced'], $configuration->get('general'));
        $this->assertNull($configuration->get('general', 'retained'));
    }

    /**
     * NOTE: This probably isn't desirable, but it's the way the old code works so here it is.
     *
     * Verify filename order determines precedence even when files are created in
     * reverse order, so numbered module and local overrides behave predictably.
     */
    public function testLaterFilenameWinsRegardlessOfCreationOrder(): void
    {
        $this->writeOverride('20-local.ini', "[general]\ntitle = Local\n");
        $this->writeOverride('10-module.ini', "[general]\ntitle = Module\n");

        $configuration = new IniConfiguration($this->iniFile);

        $this->assertSame('Local', $configuration->get('general', 'title'));
    }

    /**
     * Verify files without the .ini extension are not parsed, so that other
     * unrelated files in the override directory cannot break configuration loading.
     */
    public function testNonIniFilesAreIgnored(): void
    {
        $this->writeOverride('notes.txt', "This is not valid INI: [\n");

        $configuration = new IniConfiguration($this->iniFile);

        $this->assertSame('Original', $configuration->get('general', 'title'));
    }

    /**
     * NOTE: this is new behavior, but I think saving the exceptions for when it's actually an exception to be a good
     * idea.
     *
     * Verify absent sections and keys return null or the caller's typed fallback,
     * establishing optional-setting behavior instead of legacy lookup exceptions.
     */
    public function testMissingSectionsAndKeysReturnDefaults(): void
    {
        $configuration = new IniConfiguration($this->iniFile);

        $this->assertNull($configuration->get('missing'));
        $this->assertNull($configuration->get('general', 'missing'));
        $this->assertSame(['fallback'], $configuration->get('missing', null, ['fallback']));
        $this->assertSame(false, $configuration->get('missing', 'key', false));
        $this->assertSame(42, $configuration->get('general', 'missing', 42));
    }

    /**
     * Verify normal INI scanning produces the expected strings and arrays and that
     * empty strings and zero survive lookup rather than being replaced by defaults.
     */
    public function testParsingPreservesStringsArraysAndFalseLikeValues(): void
    {
        file_put_contents($this->iniFile, "[values]\nempty = \"\"\nzero = 0\ndisabled = false\n"
            . "enabled = true\nnull_value = null\nitems[] = first\nitems[] = second\n");

        $configuration = new IniConfiguration($this->iniFile);

        foreach (['empty' => '', 'zero' => '0', 'disabled' => '', 'enabled' => '1', 'null_value' => ''] as $key => $value) {
            $this->assertSame($value, $configuration->get('values', $key, 'fallback'), $key);
        }
        $this->assertSame(['first', 'second'], $configuration->get('values', 'items'));
    }

    /**
     * Verify a missing base file raises RuntimeException identifying its path,
     * preventing silent startup with missing required configuration.
     */
    public function testMissingBaseFileIsRejected(): void
    {
        unlink($this->iniFile);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Could not read settings file: ' . $this->iniFile);

        new IniConfiguration($this->iniFile);
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

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to parse settings file: ' . $path);

        // parse_ini_file emits a warning before returning false. Suppress only
        // that expected parser warning so the class's exception can be checked.
        set_error_handler(static function (int $severity, string $message) use ($path): bool {
            return $severity === E_WARNING
                && str_contains($message, 'syntax error')
                && str_contains($message, $path);
        });
        try {
            new IniConfiguration($this->iniFile);
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
     * Verify an empty base file is valid and lookups use their fallbacks,
     * distinguishing a successful empty parse from a syntax error as legacy code does.
     */
    public function testEmptyBaseFileIsAccepted(): void
    {
        file_put_contents($this->iniFile, '');
        $configuration = new IniConfiguration($this->iniFile);

        $this->assertNull($configuration->get('general'));
        $this->assertSame('fallback', $configuration->get('general', 'title', 'fallback'));
    }

    /**
     * Verify an empty partial is a harmless no-op, allowing placeholder override
     * files without rejecting or erasing the base configuration.
     */
    public function testEmptyOverrideIsAcceptedWithoutChangingBaseValues(): void
    {
        $this->writeOverride('empty.ini', '');
        $configuration = new IniConfiguration($this->iniFile);

        $this->assertSame(['title' => 'Original', 'retained' => 'Keep'], $configuration->get('general'));
    }

    /**
     * Verify empty-string and zero-string section names raise the documented
     * InvalidArgumentException, recording the validation imposed by empty().
     *
     * @dataProvider provideEmptySections
     */
    public function testEmptySectionIsRejectedWithInvalidArgumentException(string $section): void
    {
        $configuration = new IniConfiguration($this->iniFile);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The section must be provided.');

        $configuration->get($section);
    }

    public static function provideEmptySections(): array
    {
        return ['empty string' => [''], 'zero string' => ['0']];
    }

    /**
     * Verify empty keys use missing-key fallbacks and a configured zero key is
     * accessible, documenting that key validation differs from the legacy API.
     */
    public function testEmptyKeyReturnsDefaultAndZeroKeyCanBeRetrieved(): void
    {
        file_put_contents($this->iniFile, "[general]\n0 = Zero\n");
        $configuration = new IniConfiguration($this->iniFile);

        $this->assertNull($configuration->get('general', ''));
        $this->assertSame('fallback', $configuration->get('general', '', 'fallback'));
        $this->assertSame('Zero', $configuration->get('general', '0'));
    }

    /**
     * Verify legacy lookups throw the exact Exception class and expected messages
     * for missing or empty arguments, contrasting the new API's defaults and
     * validation. Seed and restore its cache to avoid using installed settings.
     *
     * @dataProvider provideLegacyLookupErrors
     */
    public function testLegacyLookupErrors(string $section, ?string $key, bool $wholeSection, string $message): void
    {
        $hadCache = array_key_exists('iniData', $GLOBALS);
        $original = $GLOBALS['iniData'] ?? null;
        $GLOBALS['iniData'] = ['general' => ['title' => 'Original', 0 => 'Zero']];
        try {
            try {
                if ($wholeSection) {
                    \xd_utilities\getConfigurationSection($section);
                } else {
                    \xd_utilities\getConfiguration($section, $key);
                }
                $this->fail('Expected the legacy lookup to throw.');
            } catch (\Exception $exception) {
                $this->assertSame(\Exception::class, get_class($exception));
                $this->assertSame($message, $exception->getMessage());
            }
        } finally {
            if ($hadCache) {
                $GLOBALS['iniData'] = $original;
            } else {
                unset($GLOBALS['iniData']);
            }
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
        $configuration = new IniConfiguration($this->iniFile);
        unlink($this->iniFile);

        $this->assertSame('Original', $configuration->get('general', 'title'));
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Could not read settings file: ' . $this->iniFile);
        new IniConfiguration($this->iniFile);
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

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Could not read settings file: ' . $path);

        new IniConfiguration($this->iniFile);
    }

    public static function provideUnreadableFiles(): array
    {
        return ['base file' => [false], 'override file' => [true]];
    }

    /**
     * Verify edits affect newly constructed instances while existing instances keep
     * their original values, documenting when requests or workers see file changes.
     */
    public function testExistingInstanceKeepsSnapshotWhileNewInstanceReadsChanges(): void
    {
        $configuration = new IniConfiguration($this->iniFile);
        file_put_contents($this->iniFile, "[general]\ntitle = Updated\n");

        $this->assertSame('Original', $configuration->get('general', 'title'));
        $this->assertSame('Updated', (new IniConfiguration($this->iniFile))->get('general', 'title'));
    }
}
