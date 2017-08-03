<?php namespace Models;

/**
 * Class Statistic
 *
 * @method integer getStatisticId()
 * @method void    setStatisticId($statisticId)
 * @method integer getModuleId()
 * @method void    setModuleId($moduleId)
 * @method integer getRealmId()
 * @method void    setRealmId($realmId)
 * @method string  getName()
 * @method void    setName($name)
 * @method string  getDisplay()
 * @method void    setDisplay($display)
 * @method string  getFormula()
 * @method void    setFormula($formula)
 * @method string  getAlias()
 * @method void    setAlias($alias)
 * @method string  getUnit()
 * @method void    setUnit($unit)
 * @method integer getDecimals()
 * @method void    setDecimals($decimals)
 * @method string  getDescription()
 * @method void    setDescription($description)
 * @method boolean getVisible()
 * @method void    setVisible($visible)
 */
class Statistic extends DBObject
{
    protected $PROP_MAP = array(
        'statistic_id' => 'statisticId',
        'module_id' => 'moduleId',
        'realm_id' => 'realmId',
        'name' => 'name',
        'display'=> 'display',
        'formula' => 'formula',
        'alias'=> 'alias',
        'unit' => 'unit',
        'decimals' => 'decimals',
        'description' => 'description',
        'visible' => 'visible'
    );
}
