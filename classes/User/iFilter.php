<?php namespace User;

interface iFilter
{
    /**
     * @return integer
     */
    public function getFilterId();

    /**
     * @param integer $filterId
     * @return void
     */
    public function setFilterId($filterId);

    /**
     * @return string
     */
    public function getContext();

    /**
     * @param string $context
     * @return void
     */
    public function setContext($context);

    /**
     * @return string
     */
    public function getKey();

    /**
     * @param string $key
     * @return void
     */
    public function setKey($key);

}
