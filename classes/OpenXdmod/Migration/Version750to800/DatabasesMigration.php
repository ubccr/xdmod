<?php
namespace OpenXdmod\Migration\Version750To800;

/**
 * Migrate databases from version 7.5.0 to 8.0.0.
 */
class DatabasesMigration extends \OpenXdmod\Migration\DatabasesMigration
{
    /**
     * @see \OpenXdmod\Migration\Migration::__construct
     **/
    public function __construct($currentVersion, $newVersion)
    {
        parent::__construct($currentVersion, $newVersion);
    }

    /**
     * @see \OpenXdmod\Migration\Migration::execute
     **/
    public function execute()
    {
        parent::execute();
    }
}
