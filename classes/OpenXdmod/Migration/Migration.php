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
    public function __construct($currentVersion, $newVersion)
    {
        $this->currentVersion = $currentVersion;
        $this->newVersion     = $newVersion;

        $this->logger = Log::singleton('null');
    }

    /**
     * Set the logger.
     *
     * @param LoggerInterface $logger The Monolog Logger instance.
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Execute the migration.
     */
    abstract public function execute();
}
