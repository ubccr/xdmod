<?php
/**
 * Abstract base class for Open XDMoD migrations.
 *
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace OpenXdmod\Migration;

use CCR\Log;
use Configuration\Configuration;
use Psr\Log\LoggerInterface;

abstract class Migration
{

    /**
     * An empty default Configuration object.
     *
     * @var Configuration
     */
    protected $config;

    /**
     * Logger object.
     *
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Constructor.
     *
     * @param string $currentVersion The current Open XDMoD version.
     * @param string $newVersion The version to migrate to.
     */
    public function __construct(/**
     * Version before migration.
     */
    protected $currentVersion, /**
     * Version to migrate to.
     */
    protected $newVersion)
    {
        $this->logger = Log::singleton('null');

        $this->config = Configuration::factory(
            ".",
            CONFIG_DIR,
            $this->logger
        );

    }

    /**
     * Set the logger.
     *
     * @param LoggerInterface $logger The Monolog Logger instance.
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * Execute the migration.
     */
    abstract public function execute();
}
