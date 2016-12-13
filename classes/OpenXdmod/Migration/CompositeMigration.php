<?php
/**
 * Encapsulate multiple migrations.
 *
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace OpenXdmod\Migration;

class CompositeMigration extends Migration
{

    /**
     * The migrations.
     *
     * @var array
     */
    protected $migrations = array();

    /**
     * Constructor.
     *
     * @param string $currentVersion The current Open XDMoD version.
     * @param string $newVersion The version to migrate to.
     * @param array $migrations The migrations.
     */
    public function __construct(
        $currentVersion,
        $newVersion,
        array $migrations
    ) {
        parent::__construct($currentVersion, $newVersion);

        $this->migrations = $migrations;
    }

    /**
     * @inheritdoc
     */
    public function setLogger(\Log $logger)
    {
        parent::setLogger($logger);

        foreach ($this->migrations as $migration) {
            $migration->setLogger($logger);
        }
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        foreach ($this->migrations as $migration) {
            $migration->execute();
        }
    }
}
