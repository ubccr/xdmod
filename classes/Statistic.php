<?php

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
class Statistic extends DBObject implements JsonSerializable
{
    const STATISTIC_ID = 'statistic_id';
    const MODULE_ID = 'module_id';
    const NAME = 'name';
    const DISPLAY = 'display';
    const ALIAS = 'alias';
    const UNIT = 'unit';
    const DECIMALS = 'decimals';

    protected $statisticId;
    protected $moduleId;
    protected $name;
    protected $display;
    protected $alias;
    protected $unit;
    protected $decimals;

    /**
     * @inheritdoc
     */
    function jsonSerialize()
    {
        return array(
            static::STATISTIC_ID => $this->statisticId,
            static::MODULE_ID => $this->moduleId,
            static::NAME => $this->name,
            static::DISPLAY => $this->display,
            static::ALIAS => $this->alias,
            static::UNIT => $this->unit,
            static::DECIMALS => $this->decimals
        );
    }
}
