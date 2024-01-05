<?php
namespace DataWarehouse\Query\Model;

class TableField extends Field
{
    public function __construct(
        private \DataWarehouse\Query\Model\Table $_table,
        $fieldname,
        $aliasname = ''
    ) {
        parent::__construct($fieldname, $aliasname==''?$fieldname:$aliasname);
    }

    public function getQualifiedName($show_alias = false)
    {
        $ret = $this->_table->getAlias().'.'.$this->getDefinition();

        if ($show_alias == true) {
            $ret .= " as '".($this->getAlias()==''?$this->getDefinition():$this->getAlias())."'";
        }
        return $ret;
    }

    public function __toString(): string
    {
        return $this->_table->getAlias().'.'.$this->getDefinition();
    }
}
