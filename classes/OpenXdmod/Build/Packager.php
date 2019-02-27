<?php
/**
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace OpenXdmod\Build;

use Exception;
use ArrayIterator;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;

/**
 * Open XDMoD package creation class.
 */
class Packager
{

    /**
     * @var \Log
     */
    private $logger;

    /**
     * Name of the module being built.
     *
     * @var string
     */
    private $module;

    /**
     * XDMoD repository directory.
     *
     * @var string
     */
    private $srcDir;

    /**
     * Open XDMoD module directory.
     *
     * @var string
     */
    private $moduleDir;

    /**
     * Open XDMoD module configuration.
     *
     * @var Config
     */
    private $config;

    /**
     * Open XDMoD module build destination directory.
     *
     * @var string
     */
    private $destDir;

    /**
     * Temporary directory used during build process.
     *
     * @var string
     */
    private $tmpDir;

    /**
     * Full name of the package that will be built, including.
     *
     * e.g.: xdmod-4.5.2, xdmod-appkernels-5.0.0alpha1,
     *       xdmod-supremm-5.5.0beta1
     *
     * @var string
     */
    private $packageName;

    /**
     * List of all source files that are included in the module.
     *
     * Does not include the module specific source files.
     *
     * @var array
     */
    private $sourceFileList;

    /**
     * If true, build the package using a git clone.
     *
     * @var bool
     */
    private $gitClone = false;

    /**
     * The branch to use when building a package from git.
     *
     * @var string
     */
    private $gitBranch = 'open-xdmod-dev';

    /**
     * If true, run module tests.
     *
     * @var bool
     */
    private $runTests = false;

    /**
     * If true, deploy assets before packaging.
     *
     * @var bool
     */
    private $extractAssets = true;

    /**
     * Create a packager for the given module name.
     *
     * @return Packager
     */
    public static function createFromModuleName($module)
    {
        $srcDir = BASE_DIR;

        $moduleDir = $srcDir . '/open_xdmod/modules/' . $module;

        if (!is_dir($moduleDir)) {
            throw new Exception("Module '$module' not found");
        }

        $configFile = $moduleDir . '/build.json';

        if (!is_file($configFile)) {
            throw new Exception("Build config for '$module' not found");
        }

        $config = Config::createFromConfigFile($configFile);

        $destDir = $srcDir . '/open_xdmod/build';
        if (!is_dir($destDir)) {
            if (!mkdir($destDir)) {
                throw new Exception("Failed to create directory '$destDir'");
            }
        }

        $packageName = $config->getName() . '-' . $config->getVersion();

        if ($config->isPreRelease()) {
            $packageName .= $config->getPreRelease();
        }

        $logger = \Log::singleton('null');

        return new static(array(
            'module'       => $module,
            'src_dir'      => $srcDir,
            'module_dir'   => $moduleDir,
            'config'       => $config,
            'dest_dir'     => $destDir,
            'package_name' => $packageName,
            'logger'       => $logger,
        ));
    }

    /**
     * Constructor.
     */
    public function __construct(array $args)
    {
        $this->module      = $args['module'];
        $this->srcDir      = $args['src_dir'];
        $this->moduleDir   = $args['module_dir'];
        $this->config      = $args['config'];
        $this->destDir     = $args['dest_dir'];
        $this->packageName = $args['package_name'];
        $this->logger      = $args['logger'];
    }

    /**
     * Set the logger.
     *
     * @param \Log $logger
     */
    public function setLogger(\Log $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Set whether or not a git clone should be used during packaging.
     *
     * @param bool $clone True if a git clone should be used.
     */
    public function setGitClone($clone)
    {
        $this->gitClone = $clone;
    }

    /**
     * Set the branch that should be used when cloning from git.
     *
     * @param string $branch The git branch name.
     */
    public function setGitBranch($branch)
    {
        $this->gitBranch = $branch;
    }

    /**
     * Set whether or not module tests should be run.
     *
     * @var bool $runTests True if tests should be run.
     */
    public function setRunTests($runTests)
    {
        $this->runTests = $runTests;
    }

    /**
     * Set whether or not module assets should be extracted.
     *
     * @var bool $extractAssets True if assets should be extracted.
     */
    public function setExtractAssets($extractAssets)
    {
        $this->extractAssets = $extractAssets;
    }

    /**
     * Get the temporary directory path being used.
     *
     * @return string
     */
    private function getTmpDir()
    {
        if (!isset($this->tmpDir) || !is_dir($this->tmpDir)) {
            $this->tmpDir = $this->createTmpDir();
        }

        return $this->tmpDir;
    }

    /**
     * Get the directory being used to create the package.
     *
     * Creates the directory if it doesn't already exist.
     *
     * @return string
     */
    private function getPackageDir()
    {
        $packageDir = $this->getTmpDir() . '/' . $this->packageName;

        if (!is_dir($packageDir)) {
            mkdir($packageDir);
        }

        return $packageDir;
    }

    /**
     * Get a list of all the source files included in the module.
     *
     * @return array
     */
    private function getSourceFileList()
    {
        if (!isset($this->sourceFileList)) {
            $this->sourceFileList = $this->createSourceFileList();
        }

        return $this->sourceFileList;
    }

    /**
     * Create module package.
     */
    public function createPackage()
    {
        if ($this->gitClone) {
            $this->cloneSourceRepo();
        }

        if ($this->extractAssets) {
            $this->executeAssetsSetup();
        }

        $this->runCommandsPreBuild();

        if ($this->runTests) {
            $this->runModuleTests();
        }

        $this->copySourceFiles();
        $this->copyModuleFiles();
        $this->createModuleFile();
        $this->createInstallScript();
        $this->createTarFile();
        $this->cleanUp();
    }

    /**
     * Create a clone of the source repository.
     *
     * This function assumes that the source directory is a valid git
     * repository.
     */
    private function cloneSourceRepo()
    {
        $clonePath = $this->getTmpDir() . '/xdmod-clone';

        // Clone source code from git.
        $gitArgs = array(
            'git', 'clone',
            '--branch', $this->gitBranch,
            '--depth', '1',
            'file://' . $this->srcDir,
            $clonePath,
        );
        $cmd = implode(' ', array_map('escapeshellarg', $gitArgs));
        $this->executeCommand($cmd);

        if (!is_dir($clonePath)) {
            throw new Exception('Failed to clone xdmod source from git');
        }

        $this->srcDir = $clonePath;
        $this->moduleDir = $this->srcDir . '/open_xdmod/modules/' . $this->module;
    }

    /**
     * Extract assets.
     */
    private function executeAssetsSetup()
    {
        $assetScript = "$this->moduleDir/assets/setup.sh";

        if (!is_file($assetScript)) {
            $this->logger->debug('No asset setup script found');
            return;
        }

        $this->logger->debug('Executing assets setup script');
        $this->executeCommand(escapeshellcmd($assetScript));
    }

    /**
     * Run module tests.
     */
    private function runModuleTests()
    {
        $testRunner = "$this->moduleDir/tests/runtests.sh";

        if (!is_file($testRunner)) {
            $this->logger->debug('No test runner found');
            return;
        }

        $this->logger->debug('Running tests');

        exec("$testRunner 2>&1", $output, $returnVar);

        if ($returnVar != 0) {
            foreach ($output as $line) {
                $this->logger->err($line);
            }

            throw new Exception('Tests failed');
        }

        $this->logger->debug('Tests pass');

        if (count($output) > 0) {
            $this->logger->debug('BEGIN: tests output');

            foreach ($output as $line) {
                $this->logger->debug($line);
            }

            $this->logger->debug('END: tests output');
        }

    }

    /**
     * Run pre-build commands.
     */
    private function runCommandsPreBuild()
    {
        $commands = $this->config->getCommandsPreBuild();

        if (count($commands) === 0) {
            $this->logger->debug('No pre-build commands found');
            return;
        }

        $this->logger->debug('Running pre-build commands');

        chdir($this->srcDir);

        foreach ($commands as $command) {
            $this->executeCommand($command);
        }
    }

    /**
     * Attempt to create a module file that will reside within
     * CONF_DIRECTORY/modules.d named <module_name>.json.
     *
     **/
    private function createModuleFile()
    {
        $this->logger->debug("Creating Module File");

        // The name of the module is anything after the first '-' found in the configuration file
        // name.

        $delim = '-';
        $name = $this->config->getName();
        if ( false !== ($index = strpos($name, $delim)) ) {
            $name = substr($name, $index + 1);
        }

        $data = array(
            '#' => '*** WARNING: THIS FILE IS AUTOGENERATED. MODIFY AT YOUR OWN RISK ***',
            $name => array(
                "display" => ucfirst($name),
                "enabled" => true,
                "packaged_on" => date("Y-m-d H:i:s"),
                "version" => array(
                    "major" => $this->config->getVersionMajor(),
                    "minor" => $this->config->getVersionMinor(),
                    "patch" => $this->config->getVersionPatch(),
                    "pre_release" => $this->config->getVersionPreRelease(),
                    "value" => $this->config->getVersion()
                )
            )
        );
        $modulesDirectory = implode(
            DIRECTORY_SEPARATOR,
            array(
                $this->getPackageDir(),
                "configuration",
                "modules.d"
            )
        );

        if (!is_dir($modulesDirectory)) {
            mkdir($modulesDirectory);
        }

        $destination = implode(
            DIRECTORY_SEPARATOR,
            array(
                $modulesDirectory,
                "$name.json"
            )
        );

        \CCR\Json::saveFile($destination, $data);

        $this->logger->info("Generated Module Json File: $destination");
    }

    /**
     * Copy files from source to temporary location.
     */
    private function copySourceFiles()
    {
        $this->logger->debug('Copying source files');

        $files = $this->getSourceFileList();

        $srcDir  = $this->srcDir;
        $destDir = $this->getPackageDir();

        foreach ($files as $file) {
            $srcFile  = $srcDir  . $file;
            $destFile = $destDir . $file;

            if (is_dir($srcFile)) {
                if (!mkdir($destFile)) {
                    throw new Exception("Failed to create directory '$destFile'");
                }
            } else {
                $this->copyFile($srcFile, $destFile);
            }
        }
    }

    private function processRpmSpec()
    {
        $specfilename = $this->moduleDir . '/' . $this->config->getName() . '.spec';
        $destFile = $this->getPackageDir() . '/' . $this->config->getName() . '.spec';

        if (is_file("$specfilename.in")) {
            $spec = file_get_contents("$specfilename.in");
            $spec = str_replace('__VERSION__', $this->config->getVersion(), $spec);

            if ($this->config->isPreRelease()) {
                $prerelease = $this->config->getPreRelease();
                $release = $this->config->getRelease() . '.' . $prerelease;

                $spec = str_replace('__PRERELEASE__', $prerelease, $spec);
                $spec = str_replace('__RELEASE__', $release, $spec);
            } else {
                $spec = str_replace('__PRERELEASE__', '', $spec);
                $spec = str_replace('__RELEASE__', $this->config->getRelease(), $spec);
            }

            file_put_contents($destFile, $spec);

            $this->logger->info("Generated spec file: $specfilename");
        } elseif (is_file($specfilename)) {
            $this->copyFile($specfilename, $destFile);
        } else {
            $this->logger->warning("Missing module file '$specfilename'");
        }
    }

    private function copyModuleFiles()
    {
        $this->logger->debug('Copying modules files');

        $destDir = $this->getPackageDir();
        $srcDir  = $this->moduleDir;

        $files = array(
            'AUTHORS.md',
            'CHANGELOG.md',
            'LICENSE',
            'README.md',
        );

        foreach ($files as $file) {
            $srcFile  = $srcDir  . '/' . $file;
            $destFile = $destDir . '/' . $file;

            if (!is_file($srcFile)) {
                $this->logger->warning("Missing module file '$file'");
                continue;
            }

            $this->copyFile($srcFile, $destFile);
        }

        $optionalFiles = array(
            'NOTICE',
        );

        // Add spec file.
        $this->processRpmSpec();


        foreach ($optionalFiles as $file) {
            $srcFile  = $srcDir  . '/' . $file;
            $destFile = $destDir . '/' . $file;

            if (!is_file($srcFile)) {
                continue;
            }

            $this->copyFile($srcFile, $destFile);
        }

        // All optional.
        $dirs = array_diff(scandir($srcDir), array(
            '.',
            '..',
            '.git',
            '.github',
            'assets',
            'docs',
            'automated_tests',
            'component_tests',
            'integration_tests',
            'regression_tests',
            'tests',
        ));

        foreach ($dirs as $dir) {
            $srcFile  = $srcDir  . '/' . $dir;
            $destFile = $destDir . '/' . $dir;

            if (!is_dir($srcFile)) {
                continue;
            }

            $this->copyDir($srcFile, $destFile);
        }

        // TODO: Copy files specified in build.json
    }

    private function createInstallScript()
    {
        $this->logger->debug('Creating installation script');

        $template = new BuildTemplate('install');

        // Escape single-quotes in the file maps, even if there should
        // never be a file path containing a single-quote.
        $fileMaps = str_replace(
            "'",
            "\\'",
            json_encode($this->config->getFileMaps())
        );

        $template->apply(array(
            'name'      => $this->config->getName(),
            'version'   => $this->config->getVersion(),
            'file_maps' => $fileMaps,
        ));

        $installFile = $this->getPackageDir() . '/install';

        $template->saveTo($installFile);
        chmod($installFile, 0775);
    }

    /**
     * Create a tar file.
     */
    private function createTarFile()
    {
        $tmpDir = $this->getTmpDir();
        $srcDir = $this->packageName;
        $tarFile = $this->destDir . '/' . $this->packageName . '.tar.gz';

        if (is_file($tarFile)) {
            $this->logger->debug("Removing existing tar file '$tarFile'");
            unlink($tarFile);
        }

        $this->logger->debug("Creating tar file '$tarFile'");
        $cmd = "tar zcf $tarFile -C $tmpDir $srcDir";
        $this->executeCommand(escapeshellcmd($cmd));

        $this->logger->info("Package built: $tarFile");
    }

    private function cleanUp()
    {
        $this->removeDir($this->getTmpDir());
    }

    /**
     * Create a temporary directory to use while creating the package.
     *
     * @return string The temporary directory path.
     */
    private function createTmpDir()
    {
        $this->logger->debug('Creating temporary directory');

        $tmp = tempnam(sys_get_temp_dir(), $this->config->getName() . '-');

        if (file_exists($tmp)) {
            $this->logger->debug("Unlinking file '$tmp'");
            unlink($tmp);
        }

        $this->logger->debug("Creating directory '$tmp'");
        if (!mkdir($tmp) || !is_dir($tmp)) {
            throw new Exception('Failed to create temporary directory');
        }

        return $tmp;
    }

    private function createSourceFileList()
    {
        $this->logger->debug('Creating source file list');

        // Length of source directory path, used to shorten paths so
        // that they are all relative to this directory.
        $srcLength = strlen($this->srcDir);

        $srcIter = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $this->srcDir,
                FilesystemIterator::SKIP_DOTS
            ),
            RecursiveIteratorIterator::SELF_FIRST
        );

        // List of all source files.
        $srcFiles = array();

        // File info for all source files.
        $srcFileInfo = array();

        foreach ($srcIter as $path => $info) {

            // Remove source path prefix.
            $relPath = substr($path, $srcLength);

            $srcFiles[] = $relPath;
            $srcFileInfo[$relPath] = $info;
        }

        // The files that will be included in the package.
        $includeFiles = array();

        // The directories that are specified in the include paths.
        // Used to build a list of all the files included in the
        // directory.
        $includeDirs = array();

        // Build lists of all files and directories from the include
        // path list.
        foreach ($this->config->getFileIncludePaths() as $path) {

            // Special case to include all files.
            if ($path === '/') {
                $includeFiles = $srcFiles;
                break;
            }

            if (!isset($srcFileInfo[$path])) {
                $this->logger->warning("Included path '$path' not found");
                continue;
            }

            $includeFiles[] = $path;

            if ($srcFileInfo[$path]->isDir()) {
                $includeDirs[] = $path;
            }
        }

        // Add all files in included directories to the file list.
        foreach ($includeDirs as $dir) {
            $includeDirFiles = array();

            foreach ($srcFiles as $file) {
                if (strpos($file, $dir) === 0) {
                    $includeDirFiles[] = $file;
                }
            }

            $includeFiles = array_merge($includeFiles, $includeDirFiles);
        }

        // Add all files that match include patterns to the file list.
        foreach ($this->config->getFileIncludePatterns() as $pattern) {
            $fileIterator = new ArrayIterator($srcFiles);
            $regexIter = new RegexIterator($fileIterator, $pattern);
            $includePatternFiles = iterator_to_array($regexIter);

            if (count($includePatternFiles) === 0) {
                $this->logger->warning("No files match include pattern '$pattern'");
            }

            $includeFiles = array_merge($includeFiles, $includePatternFiles);
        }

        // Check all files for subdirectories that are not explicitly
        // included already.
        $implicitIncludeDirs = array();

        // Used as hash to reduce the number of possible duplicates.
        $seenDirs = array();

        foreach ($includeFiles as $file) {
            if (strpos($file, '/', 1) === false) {
                continue;
            }

            $parts = explode('/', $file);

            // Skip paths with less than three parts.  The first part is
            // always empty, because paths always start with "/".  If
            // there are only two parts then the part doesn't contain
            // any subdirectories (e.g. "/templates").
            if (count($parts) < 3) {
                continue;
            }

            $dir = '';
            foreach (range(1, count($parts) - 2) as $i) {
                $dir .= '/' . $parts[$i];

                if (!isset($seenDirs[$dir])) {
                    $implicitIncludeDirs[] = $dir;
                    $seenDirs[$dir] = 1;
                }
            }
        }

        // Merge and remove duplicates.
        $includeFiles = array_merge($includeFiles, $implicitIncludeDirs);
        $includeFiles = array_unique($includeFiles);

        // Files and directories that will be excluded from the module
        // package.
        $excludeFiles = array();
        $excludeDirs = array();

        // The files in /open_xdmod are copied in a separate step, so
        // they are are excluded here.
        $excludeDirs[] = '/open_xdmod';

        // Build list of excluded files and directories.
        foreach ($this->config->getFileExcludePaths() as $path) {
            if (!isset($srcFileInfo[$path])) {
                $this->logger->debug("Excluded path '$path' not found");
                continue;
            }

            $excludeFiles[] = $path;

            if ($srcFileInfo[$path]->isDir()) {
                $excludeDirs[] = $path;
            }
        }

        // Add all files in excluded directories to the exclude list.
        foreach ($excludeDirs as $dir) {
            $excludeDirFiles = array();

            foreach ($srcFiles as $file) {
                if (strpos($file, $dir) === 0) {
                    $excludeDirFiles[] = $file;
                }
            }

            $excludeFiles = array_merge($excludeFiles, $excludeDirFiles);
        }

        // Add files to exclude based on exclude patterns.
        foreach ($this->config->getFileExcludePatterns() as $pattern) {
            $fileIterator = new ArrayIterator($srcFiles);
            $regexIter = new RegexIterator($fileIterator, $pattern);
            $excludePatternFiles = iterator_to_array($regexIter);

            if (count($excludePatternFiles) === 0) {
                $this->logger->debug("No files match exclude pattern '$pattern'");
            }

            $excludeFiles = array_merge($excludeFiles, $excludePatternFiles);
        }

        // Remove excluded files from files list.
        $includeFiles = array_diff($includeFiles, $excludeFiles);

        sort($includeFiles);

        return $includeFiles;
    }

    /**
     * Copy a single file.
     *
     * @param string $srcFile Source file path.
     * @param string $destFile Destination file path.
     */
    private function copyFile($srcFile, $destFile)
    {
        #$this->logger->debug("Copying '$srcFile' to '$destFile'");

        if (!is_file($srcFile)) {
            throw new Exception("File '$srcFile' does not exist");
        }

        if (!copy($srcFile, $destFile)) {
            throw new Exception("Failed to copy file '$srcFile' to '$destFile'");
        }

       if (!chmod($destFile, fileperms($srcFile))) {
          throw new Exception("Failed change the mode of file '$destFile'");
       }
    }

    /** Check to see if a file or directory is to be excluded from the build
     * because of the configuration settings.
     *
     * @param filePath path of file to check
     * @return true if the file is to be excluded false otherwise.
     */
    private function isFilePathExcluded($filePath)
    {
        if (array_search($filePath, $this->config->getFileExcludePaths()) !== false) {
            return true;
        }

        foreach ($this->config->getFileExcludePatterns() as $pattern) {
            if (preg_match($pattern, $filePath) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * Copy a directory recursively.
     *
     * @param string $srcDir Source directory path.
     * @param string $destDir Destination directory path.
     */
    private function copyDir($srcDir, $destDir)
    {
        if ($this->isFilePathExcluded($srcDir)) {
            return;
        }

        if (!is_dir($srcDir)) {
            throw new Exception("Directory '$srcDir' does not exist");
        }

        if (!is_dir($destDir) && !mkdir($destDir)) {
            throw new Exception("Failed to create directory '$destDir'");
        }

       if (!chmod($destDir, fileperms($srcDir))) {
          throw new Exception("Failed change the mode of directory '$destDir'");
       }

        foreach (scandir($srcDir) as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $srcFile  = "$srcDir/$file";
            $destFile = "$destDir/$file";

            if ($this->isFilePathExcluded($srcFile)) {
                continue;
            }

            if (is_file($srcFile)) {
                $this->copyFile($srcFile, $destFile);
            } elseif (is_dir($srcFile)) {
                $this->copyDir($srcFile, $destFile);
            }
        }
    }

    /**
     * Remove a directory.
     *
     * @param string $dir Directory path to remove.
     */
    private function removeDir($dir)
    {
        $this->logger->debug("Removing directory '$dir'");
        $cmd = "rm -rf $dir";
        $this->executeCommand(escapeshellcmd($cmd));
    }

    /**
     * Execute a command.
     *
     * @param string $command The command to execute.  Must already be
     *     escaped.
     *
     * @throws Exception If the command exits with a non-zero return status.
     *
     * @return array The command output.
     */
    private function executeCommand($command)
    {
        $output    = array();
        $returnVar = 0;

        $this->logger->debug("Executing command: $command");

        exec($command . ' 2>&1', $output, $returnVar);

        if ($returnVar != 0) {
            $msg = "Command exited with non-zero return status:\n"
                . "command = $command\noutput =\n" . implode("\n", $output);
            throw new Exception($msg);
        }

        if (count($output) > 0) {
            $this->logger->debug('BEGIN: command output');

            foreach ($output as $line) {
                $this->logger->debug($line);
            }

            $this->logger->debug('END: command output');
        }

        return $output;
    }
}
