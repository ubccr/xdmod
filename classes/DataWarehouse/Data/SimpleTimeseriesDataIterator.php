<?php

namespace DataWarehouse\Data;

/**
 * This class is used to iterate through the sub data series of a
 * timeseries data set. (Note that we must implement all methods
 * of the abstract class in order to use the iterator)
 * http://php.net/manual/en/class.iterator.php
 *
 * This special-purpose iterator is used for a small subset of the
 * records returned by a given timeseries query.
 * Once fetched from the database, dataObjects are stored in an array.
 *
 * @author Amin Ghadersohi
 * @author Jeanette Sperhac
 *
 */
class SimpleTimeseriesDataIterator implements \Iterator
{
    private $groupColumn; // SimpleTimeseriesData
    private $dataset; // SimpleTimeseriesDataset

    // index of the iterator
    private $index = 0;

    // Information about the groupColumn
    private $column_type_and_name;
    private $is_dimension;
    private $column_name;
    private $where_column_name;

    // use an array to keep the ids of records <= $limit
    private $limit_ids = array();
    private $dataObjects = array();

    public function __construct(
        \DataWarehouse\Data\SimpleTimeseriesDataset &$dataset,
        $column_type_and_name,
        \DataWarehouse\Data\SimpleTimeseriesData &$groupColumn
    ) {
        $this->groupColumn = $groupColumn;
        $this->dataset = $dataset;
        $this->index = 0;
        $this->column_type_and_name = $column_type_and_name;

        if ($column_type_and_name =='time') {
            $this->is_dimension = true;
            $this->column_name = $dataset->getAggregationUnit()->getUnitName();
            $this->where_column_name = $dataset->_query->getAggregationUnit()->getUnitName() . '_id';
        } else {
            $this->is_dimension = substr($column_type_and_name, 0, 3) == 'dim';
            $this->column_name = substr($column_type_and_name, 4);

            $this->where_column_name = $groupColumn->getName();
            $gpBy = $groupColumn->getGroupBy();
            if (isset($gpBy)) {
                $this->where_column_name .= '_id';
            }
        }
    }

    //-------------------------------------------------
    // public function current()
    //
    // Returns the current SimpleTimeseriesDataset object from the iterator
    //
    // @return \DataWarehouse\Data\SimpleTimeseriesData
    //-------------------------------------------------
    public function current()
    {
        if (!$this->valid()) {
            return null;
        }

        $value = $this->groupColumn->getValue($this->index);
        $id    = $this->groupColumn->getId($this->index);

        if (!in_array($id, $this->limit_ids)) {
            // TODO: instead of keeping a limit_ids array and a dataObjects array,
            // just keep an assoc array of dataObjects...

            // add the current id to the iterator object's id array
            // so these ids can be excluded from the summary query:
            $this->limit_ids[] = $id;

            $dataObject = $this->dataset->getColumn(
                $this->column_type_and_name,
                null,
                null,
                $this->where_column_name,
                $id
            );

            $dataObject->setName($value);

            $dataObject->setGroupName($value);
            $dataObject->setGroupId($this->groupColumn->getId($this->index));

            $dataObject->setUnit($this->dataset->getColumnLabel(
                $this->column_name,
                $this->is_dimension
            ));

            $this->dataObjects[$id] = $dataObject;
        } else {
            $dataObject = $this->dataObjects[$id];
        }

        // SimpleTimeseriesData object
        return $dataObject;
    }

    // reset index to 0
    public function rewind()
    {
        $this->index = 0;
    }

    // return current index
    // @return integer
    public function key()
    {
        return $this->index;
    }

    // advance index then return current
    // @return SimpleTimeseriesData object
    public function next()
    {
        ++$this->index;
        return $this->current();
    }

    public function valid()
    {
        $vals = $this->groupColumn->getValues();
        return isset($vals[$this->index]);
    }

    public function count()
    {
        return $this->groupColumn->getCount(true);
    }

    // ----- accessor functions -------

    // use an array to keep the ids of records <= $limit
    // these records are not stored in the iterator
    // @return array
    public function getLimitIds()
    {
        return $this->limit_ids;
    }
} // class SimpleTimeseriesDataIterator
