<?php
/**
 * Class for managing table foreign key constraints in the data warehouse.  This
 * is meant to be used as a component of Table.
 *
 * @see Table
 * @see iEntity
 */

namespace ETL\DbModel;

use Log;
use stdClass;

class ForeignKeyConstraint extends NamedEntity implements iEntity
{
    /**
     * Properties required by this class. These will be merged with other
     * required properties up the call chain.
     *
     * @see Entity::$requiredProperties
     */
    private $localRequiredProperties = array(
        'columns',
        'referenced_table',
        'referenced_columns',
    );

    /**
     * Properties provided by this class. These will be merged with other
     * properties up the call chain.
     *
     * @see Entity::$properties
     */
    private $localProperties = array(
        'columns' => array(),
        'referenced_table' => null,
        'referenced_columns' => array(),
        'on_delete' => null,
        'on_update' => null,
    );

    public function __construct(
        $config,
        $systemQuoteChar = null,
        Log $logger = null
    ) {
        // Property merging is performed first so the values can be used in the
        // constructor
        parent::mergeProperties(
            $this->localRequiredProperties,
            $this->localProperties
        );
        parent::__construct($config, $systemQuoteChar, $logger);
    }

    public function initialize(stdClass $config)
    {
        if (!isset($config->name)) {
            $config->name = $this->generateForeignKeyConstraintName($config->columns);
        }

        parent::initialize($config);
    }

    protected function filterAndVerifyValue($property, $value)
    {
        $value = parent::filterAndVerifyValue($property, $value);

        if ($value === null) {
            return $value;
        }

        switch ($property) {
            case 'columns':
                if (!is_array($value)) {
                    $this->logAndThrowException(
                        sprintf(
                            '"%s" must be an array, "%s" given',
                            $property,
                            gettype($value)
                        )
                    );
                } elseif (0 === count($value)) {
                    $this->logAndThrowException(
                        sprintf('"%s" must be a non-empty array', $property)
                    );
                }
                break;
            case 'referenced_table':
                if (!is_string($value)) {
                    $this->logAndThrowException(
                        sprintf(
                            '"%s" must be a string, "%s" given',
                            $property,
                            gettype($value)
                        )
                    );
                }
                break;
            case 'referenced_columns':
                if (!is_array($value)) {
                    $this->logAndThrowException(
                        sprintf(
                            '"%s" must be an array, "%s" given',
                            $property,
                            gettype($value)
                        )
                    );
                } elseif (0 === count($value)) {
                    $this->logAndThrowException(
                        sprintf('"%s" must be a non-empty array', $property)
                    );
                }
                break;
            case 'on_delete':
            case 'on_update':
                if (!is_string($value)) {
                    $this->logAndThrowException(
                        sprintf(
                            '"%s" must be a string, "%s" given',
                            $property,
                            gettype($value)
                        )
                    );
                }
                if (!in_array(
                    strtoupper($value),
                    array(
                        'RESTRICT',
                        'NO ACTION',
                        'CASCADE',
                        'SET NULL',
                        'SET DEFAULT',
                    )
                )) {
                    $this->logAndThrowException(
                        sprintf(
                            '"%s" action is not allowed for "%s"',
                            $value,
                            $property
                        )
                    );
                }
                break;
            default:
                break;
        }

        return $value;
    }

    /**
     * Auto-generate a foreign key constraint name.
     *
     * Uses the columns included in the foreign key constraint.  If the length
     * of the constraint name would be too large use a hash.
     *
     * @param array $columns The array of constraint column names
     *
     * @return string The generated foreign key constraint name
     */
    private function generateForeignKeyConstraintName(array $columns)
    {
        $str = implode('_', $columns);
        $name = ( strlen($str) <= 32 ? $str : md5($str) );

        return 'fk_' . $name;
    }

    /**
     * Foreign key constraints are considered equal if all properties are the same.
     */
    public function compare(iEntity $cmp)
    {
        if (!$cmp instanceof ForeignKeyConstraint) {
            return 1;
        }

        if ($this->name != $cmp->name
            || $this->columns != $cmp->columns
            || $this->referenced_table != $cmp->referenced_table
            || $this->referenced_columns != $cmp->referenced_columns
            || $this->on_delete != $cmp->on_delete
            || $this->on_update != $cmp->on_update
        ) {
            return -1;
        }

        return 0;
    }

    public function getSql($includeSchema = false)
    {
        $parts = array(
            'CONSTRAINT',
            $this->getName(true),
            'FOREIGN KEY',
        );
        $parts[] = '('
            . implode(', ', array_map(array($this, 'quote'), $this->columns))
            . ')';
        $parts[] = 'REFERENCES';
        $parts[] = $this->quote($this->referenced_table);
        $parts[] = '('
            . implode(
                ', ',
                array_map(array($this, 'quote'), $this->referenced_columns)
            )
            . ')';

        if ($this->on_delete !== null) {
            $parts[] = 'ON DELETE';
            $parts[] = $this->on_delete;
        }

        if ($this->on_update !== null) {
            $parts[] = 'ON UPDATE';
            $parts[] = $this->on_update;
        }

        return implode(' ', $parts);
    }
}
