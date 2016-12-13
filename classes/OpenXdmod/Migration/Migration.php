<?php
/**
 * Abstract base class for Open XDMoD migrations.
 *
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace OpenXdmod\Migration;

use Xdmod\Config;

abstract class Migration
{

    /**
     * Version before migration.
     *
     * @var string
     */
    protected $currentVersion;

    /**
     * Version to migrate to.
     *
     * @var string
     */
    protected $newVersion;

    /**
     * Config object.
     *
     * @var Xdmod\Config
     */
    protected $config;

    /**
     * Logger object.
     *
     * @var \Log
     */
    protected $logger;

    /**
     * Constructor.
     *
     * @param string $currentVersion The current Open XDMoD version.
     * @param string $newVersion The version to migrate to.
     */
    public function __construct($currentVersion, $newVersion)
    {
        $this->currentVersion = $currentVersion;
        $this->newVersion     = $newVersion;

        $this->config = Config::factory();
        $this->logger = \Log::singleton('null');
    }

    /**
     * Set the logger.
     *
     * @param Logger $logger The logger instance.
     */
    public function setLogger(\Log $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Execute the migration.
     */
    abstract public function execute();
}
