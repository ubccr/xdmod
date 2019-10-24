<?php
namespace DataWarehouse\Query\Model;

class Table extends \Common\Identity
{
    protected $_schema;
    protected $_alias;
    protected $_join_index;

    public function __construct(
        \DataWarehouse\Query\Model\Schema $schema,
        $tablename,
        $aliasname,
        $join_index = ''
    ) {
        parent::__construct($tablename);
        $this->_alias = new \DataWarehouse\Query\Model\Alias($aliasname);
        $this->_schema = $schema;
        $this->_join_index = $join_index;
    }
    public function getJoinIndex()
    {
        return $this->_join_index;
    }
    public function getSchema()
    {
        return $this->_schema;
    }

    public function getAlias()
    {
        if ($this->_alias == '') {
            return $this->getName();
        } else {
            return $this->_alias;
        }
    }

    public function getQualifiedName($show_alias = false, $show_join_hint = false)
    {
        $ret = '';
        if ($this->_schema  == '') {
            $ret = $this->getName();
        } else {
            $ret = $this->_schema.'.'.$this->getName();
        }
        if ($this->getAlias() != '' && $show_alias == true) {
            $ret .= ' '.$this->getAlias();
        }
        if ($this->getJoinIndex() != '' && $show_join_hint == true) {
            $ret .= ' use index ('.$this->getJoinIndex().')';
        }
        return $ret;
    }
}
