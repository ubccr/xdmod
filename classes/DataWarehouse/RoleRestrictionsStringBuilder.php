<?php

namespace DataWarehouse;

/**
 * Builds a set of strings and representative symbols for sets of role restrictions.
 */
class RoleRestrictionsStringBuilder
{
    /**
     * The set of unique role restriction lists used by this builder.
     *
     * @var array
     */
    private $roleRestrictionsLists = array();

    /**
     * Generate a string describing each registered role restriction list.
     *
     * @return array The strings representing each list.
     */
    public function getRoleRestrictionsStrings()
    {
        $roleRestrictionsStrings = array();
        foreach ($this->roleRestrictionsLists as $roleRestrictionsList) {
            $symbol = $roleRestrictionsList['symbol'];

            $parameterStrings = array();
            foreach ($roleRestrictionsList['parameters'] as $dimensionId => $parameterOptions) {
                $parameterGroupBy = $parameterOptions['groupBy'];
                $parameterValueNames = array();
                foreach ($parameterOptions['dimensionValues'] as $valueId) {
                    $parameterPossibleValues = $parameterGroupBy->getPossibleValues(array(
                        'id' => $valueId,
                    ));
                    if (empty($parameterPossibleValues)) {
                        $parameterValueNames[] = '[Value Name Not Found]';
                    } else {
                        $parameterValueNames[] = $parameterPossibleValues[0]['short_name'];
                    }
                }
                $parameterValueNamesString = implode(', ', $parameterValueNames);
                if (count($parameterValueNames) > 1) {
                    $parameterValueNamesString = "($parameterValueNamesString)";
                }
                $parameterLabel = $parameterGroupBy->getLabel();
                $parameterStrings[$parameterLabel] = $parameterLabel . ' = ' . $parameterValueNamesString;
            }
            ksort($parameterStrings);

            $roleRestrictionsStrings[] = $symbol . 'Restricted To: ' . implode(' OR ', $parameterStrings);
        }
        return $roleRestrictionsStrings;
    }

    /**
     * Register the given set of role restriction parameters.
     *
     * This function will store unique parameter lists so that their symbols
     * may be reused by data series restricted in the same way.
     *
     * @param  array  $roleRestrictionsParameters The parameters used to
     *                                            restrict a data series.
     * @return string                             A symbol representing these
     *                                            parameters uniquely.
     */
    public function registerRoleRestrictions($roleRestrictionsParameters)
    {
        // If the given list is empty, return an empty string.
        $numRoleRestrictionsParameters = count($roleRestrictionsParameters);
        if ($numRoleRestrictionsParameters === 0) {
            return '';
        }

        // Check if the given list matches any registered list.
        // If so, return the symbol used for that list.
        foreach ($this->roleRestrictionsLists as $roleRestrictionsList) {
            $currentParameters = $roleRestrictionsList['parameters'];
            if (count($currentParameters) !== $numRoleRestrictionsParameters) {
                continue;
            }

            foreach ($currentParameters as $currentDimensionId => $currentParameterOptions) {
                if (!array_key_exists($currentDimensionId, $roleRestrictionsParameters)) {
                    continue 2;
                }

                $currentParameterValues = $currentParameterOptions['dimensionValues'];
                $givenParameterValues = $roleRestrictionsParameters[$currentDimensionId]['dimensionValues'];

                if (count($currentParameterValues) !== count($givenParameterValues)) {
                    continue 2;
                }

                foreach ($currentParameterValues as $currentParameterValue) {
                    if (!in_array($currentParameterValue, $givenParameterValues)) {
                        continue 3;
                    }
                }
            }

            return $roleRestrictionsList['symbol'];
        }

        // Register the list and return its symbol.
        $numAsterisks = count($this->roleRestrictionsLists) + 1;
        $symbol = str_repeat('*', $numAsterisks);
        $this->roleRestrictionsLists[] = array(
            'symbol' => $symbol,
            'parameters' => $roleRestrictionsParameters,
        );

        return $symbol;
    }
}
