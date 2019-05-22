<?php

namespace DataWarehouse\Query\Model;

class Field
{

    /**
     * The field definition.
     *
     * @var string
     */
    protected $_def;

    /**
     * The field alias.
     *
     * @var \DataWarehouse\Query\Model\Alias
     */
    protected $_alias;

    /**
     * Construct the field.
     *
     * @param string $field_def A SQL expression defining the field
     * @param string $aliasname An optional alias for the field.
     */
    public function __construct($field_def, $aliasname = '')
    {
        $this->_def   = $field_def;
        $this->_alias = new \DataWarehouse\Query\Model\Alias($aliasname);
    }

    /**
     * Definition accessor.
     *
     * @return string
     */
    public function getDefinition()
    {
        return $this->_def;
    }

    /**
     * Alias accessor.
     *
     * @return \DataWarehouse\Query\Model\Alias
     */
    public function getAlias()
    {
        return $this->_alias;
    }

    /**
     * Returns the qualified name of this field.
     *
     * The qualified name is the fields definition with the alias
     * optionally included.
     *
     * @param bool $show_alias True if the alias show be included in the
     *    qualified name of the field.
     *
     * @return string
     */
    public function getQualifiedName($show_alias = false)
    {
        $ret = $this->_def;

        if ($show_alias == true && $this->getAlias() != '') {
            $ret .= ' as ' . $this->getAlias();
        }

        return $ret;
    }

    /**
     * Returns the field definition.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->_def;
    }
}
