<?php namespace Models;


/**
 * Class Statistic
 *
 * @method integer getStatisticId()
 * @method void    setStatisticId($statisticId)
 * @method integer getModuleId()
 * @method void    setModuleId($moduleId)
 * @method string  getName()
 * @method void    setName($name)
 * @method string  getDisplay()
 * @method void    setDisplay($display)
 * @method string  getAlias()
 * @method void    setAlias($alias)
 * @method string  getUnit()
 * @method void    setUnit($unit)
 * @method integer getDecimals()
 * @method void    setDecimals($decimals)
 */
class Statistic extends DBObject
{
    protected $PROP_MAP = array(
        'statistic_id' => 'statisticId',
        'module_id' => 'moduleId',
        'name' => 'name',
        'display'=> 'display',
        'alias'=> 'alias',
        'unit' => 'unit',
        'decimals' => 'decimals'
    );
}
