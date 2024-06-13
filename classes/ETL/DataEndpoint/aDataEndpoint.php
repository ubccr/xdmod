<?php
/**
 * Abstract helper class that provides functionality to support the implementation of iDataEndpoint.
 * Developers are not required to extend this class but it will make life easier.
 */

namespace ETL\DataEndpoint;

use ETL\aEtlObject;
use ETL\DataEndpoint\iDataEndpoint;
use Exception;
use Psr\Log\LoggerInterface;

abstract class aDataEndpoint extends aEtlObject
{
    /**
     * @var string The endpoint type (e.g., mysql, pdo, file, url)
     */
    protected $type = null;

    /**
     * @var resource A handle to the actual class or file descriptor that implements the endpoint
     */
    protected $handle = null;

    /**
     * @var string A unique key that can be used to identify this endpoint. Typically some
     * combination of type and nat me.
     */
    protected $key = null;

    /**
     * @var string Default separator used in key generation.
     */
    protected $keySeparator = '|';

    /**
     * @var int The current index for keys generated using the default method provided by this
     * class. Note that this method does not allow for de-duplication of data endpoint resources and
     * child classes should ideally implement their own version of generateUniqueKey().
     */
    private static $currentUniqueKeyIndex = 0;

    /**
     * @see iDataEndpoint::__construct()
     */

    public function __construct(DataEndpointOptions $options, LoggerInterface $logger = null)
    {
        parent::__construct($logger);

        $requiredKeys = array("name", "type");
        $this->verifyRequiredConfigKeys($requiredKeys, $options);

        $messages = array();
        $propertyTypes = array(
            'name' => 'string',
            'type' => 'string'
        );

        if ( ! \xd_utilities\verify_object_property_types($options, $propertyTypes, $messages, true) ) {
            $this->logAndThrowException("Error verifying options: " . implode(", ", $messages));
        }

        $this->type = $options->type;
        $this->setName($options->name);
    }

    /**
     *  @see iDataEndpoint::getType()
     */

    public function getType()
    {
        return $this->type;
    }

    /**
     *  @see iDataEndpoint::getKey()
     */

    public function getKey(): ?string
    {
        return $this->key;
    }

    /**
     *  @see iDataEndpoint::getHandle()
     */

    public function getHandle()
    {
        return ( null !== $this->handle ? $this->handle : $this->connect() );
    }

    /**
     * @see iDataEndpoint::quote()
     *
     * By default, do nothing unless the underlying endpoint driver specifies a quoting method.
     */

    public function quote($str)
    {
        return $str;
    }

    /**
     * @see iDataEndpoint::isSameServer()
     *
     * By default we return false but the child class should override this based on a specific type of
     * endpoint.
     */

    public function isSameServer(iDataEndpoint $cmp)
    {
        return false;
    }

    /**
     * Generate and store a key that uniquely identifies this data endpoint. This is used to
     * determine if this endpoint is being used in multiuple locations and allows us to re-use
     * endpoint objects where possible.
     *
     * This may be used by aDataEndpoint subclass that doesn't have a way to identify reusable
     * endpoints.
     */

    protected function generateUniqueKey()
    {
        $this->key = sprintf("%s-%d", get_class($this), self::$currentUniqueKeyIndex++);
    }

    /**
     * See iDataEndpoint::connect()
     */

    abstract public function connect();
}
