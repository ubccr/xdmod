<?php

namespace Xdmod;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

use Composer\Script\Event;

class ComposerScripts
{
    /**
     * A mapping of package names to lists of files to keep post-install.
     *
     * @var array
     */
    private static $packageWhitelists = array(
        'apache/xalan-j-2jars' => array(
            'xalan.jar',
        ),
        'apache/poi' => array(
            'poi-3.6-20091214.jar',
        ),
        'apache/commons-beanutils' => array(
            'commons-beanutils-1.8.0.jar',
        ),
        'apache/commons-collections' => array(
            'commons-collections-2.1.1.jar',
        ),
        'apache/commons-digester' => array(
            'commons-digester-1.7.jar',
        ),
        'apache/commons-logging' => array(
            'commons-logging.jar',
        ),
    );

    public static function postInstall(Event $event)
    {
        $composer = $event->getComposer();
        $repositoryManager = $composer->getRepositoryManager();
        $installationManager = $composer->getInstallationManager();

        foreach (static::$packageWhitelists as $packageName => $packageWhitelist) {
            $package = $repositoryManager->findPackage($packageName, '*');
            if ($package === null) {
                echo "Could not find package '$packageName' during cleanup.\n";
                continue;
            }

            $packagePath = $installationManager->getInstallPath($package);
            static::deleteFilesInDirExcept($packagePath, array_map(
                function ($packageWhitelistPath) use ($packagePath) {
                    return "$packagePath/$packageWhitelistPath";
                },
                $packageWhitelist
            ));
        }
    }

    public static function postUpdate(Event $event)
    {
        return static::postInstall($event);
    }

    /**
     * Delete all files in a directory except some given exceptions.
     *
     * @param  string $dir        The directory to delete the contents of.
     * @param  array  $exceptions A set of paths to not delete.
     */
    private static function deleteFilesInDirExcept($dir, array $exceptions = array())
    {
        $fileIterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $dir,
                FilesystemIterator::SKIP_DOTS
            ),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($fileIterator as $file) {
            $filePath = $file->getPathname();
            if (in_array($filePath, $exceptions)) {
                continue;
            }

            if ($file->isDir()) {
                @rmdir($filePath);
            } else {
                unlink($filePath);
            }
        }
    }
}
