<?php

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
class Filter extends DBObject
{
    protected $PROP_MAP = array(
        'filter_id'=> 'filterId',
        'context'=> 'context',
        'key' => 'key'
    );
}
