<?php
namespace UnitTesting\OpenXdmod\Migrations\Version560To600;

use ReflectionObject;

use PHPUnit_Framework_TestCase;
use xd_utilities;
use OpenXdmod\Migration\Version560To600\ConfigFilesMigration;
use CCR\Json;

class ConfigFilesMigrationTest extends PHPUnit_Framework_TestCase
{
    private $verbose;

    /**
     * @SuppressWarnings(PHPMD.StaticAccess)
     **/
    public function setUp()
    {
        $currentVersion = "5.6.0";
        $newVersion = "6.0.0";
        $this->verbose = true;
        $this->migration = $this->getMockBuilder('OpenXdmod\Migration\Version560To600\ConfigFilesMigration')
                         ->setConstructorArgs(array($currentVersion, $newVersion, $this->verbose))
                         ->setMethods(array('validateModification'))
                         ->getMock();
    }

    public function testModification()
    {
        $self = $this;
        $this->migration
            ->method('validateModification')
            ->will($this->returnCallback(function (array $modified, array $modifications) use ($self) {
                $intersection = array_intersect_assoc($modified, $modifications);
                $valid = $intersection == $modifications;

                $selfReflection = new ReflectionObject($self);
                $selfVerboseReflection = $selfReflection->getProperty('verbose');
                $selfVerboseReflection->setAccessible(true);
                $selfVerbose = $selfVerboseReflection->getValue($self);

                if ($selfVerbose && !$valid) {
                    echo "Modification not successful: \n";
                    echo "Expected: \n";
                    echo Json::prettyPrint(json_encode($modifications))."\n";
                    echo "Found: \n";
                    echo Json::prettyPrint(json_encode($intersection))."\n";
                    echo "********\n";
                }
                $self->assertTrue($valid);
                return $valid;
            }));
        $files = $this->migration->getSourceFiles();
        foreach ($files as $file) {
            $this->migration->modifyFile($file);
        }
    }
}
