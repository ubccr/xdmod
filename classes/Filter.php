<?php

/**
 * Class Filter
 *
 * @method integer getFilterId()
 * @method void    setFilterId($filterId)
 * @method string  getContext()
 * @method void    setContext($context)
 * @method string  getKey()
 * @method void    setKey($key)
 */
class Filter extends DBObject implements JsonSerializable
{
    const FILTER_ID = 'filter_id';
    const CONTEXT = 'context';
    const KEY = 'key';

    protected $filterId;
    protected $context;
    protected $key;

    /**
     * @inheritdoc
     */
    function jsonSerialize()
    {
        return array(
            static::FILTER_ID => $this->filterId,
            static::CONTEXT => $this->context,
            static::KEY => $this->key
        );
    }
}
