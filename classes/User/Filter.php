<?php namespace User;

use DBObject;

/**
 * Class Filter
 * @package User
 *
 * @method integer getFilterId()
 * @method void    setFilterId($filterId)
 * @method string  getContext()
 * @method void    setContext($context)
 * @method string  getKey()
 * @method void    setKey($key)
 */
class Filter extends DBObject implements iFilter
{

    /**
     * @var integer
     */
    protected $filterId;

    /**
     * @var string
     */
    protected $context;

    /**
     * @var string
     */
    protected $key;
}
