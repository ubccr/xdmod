<?php

namespace Common;

/**
 * This is the parent class for any class that has a name attribute.
 *
 * @author Amin Ghadersohi
 */
class Identity
{

    /**
     * The name of the object.
     *
     * @var string
     */
    private $_name;

    /**
     * Simple constructor that sets the name.
     *
     * @param string $name
     */
    public function __construct($name)
    {
        $this->setName($name);
    }

    /**
     * Name accessor.
     *
     * @return string
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * Name mutator.
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->_name = $name;
    }

    /**
     * Returns the name of the object.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getName();
    }
}
