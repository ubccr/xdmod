<?php

namespace CCR;

/**
 * Helper class to organise the layout of items in columns
 */
class ColumnLayout
{
    private $nextRow = array();
    private $nextColumn = 0;
    private $settings = array();

    /*
     * @param $columns the default number of columns to manage.
     * @param $defaults an array containing the zero-based row and column index for
     *        each item that may be layed out. The column indexes in the defaults
     *        may override the number of columns specified in the $columns argument.
     */
    public function __construct($columns, $defaults = null)
    {
        $this->nextRow = array_fill(0, $columns, 0);

        if ($defaults !== null) {
            $this->settings = $defaults;

            foreach ($this->settings as $itemId => $rowcol)
            {
                list($row, $col) = $rowcol;

                if (count($this->nextRow) < ($col + 1)) {
                    $this->nextRow = array_pad($this->nextRow, $col + 1, 0);
                }

                $this->nextRow[$col] = max($row + 1, $this->nextRow[$col]);
            }
        }
    }

    /**
     * number of columns managed by this instance
     */
    public function getColumnCount()
    {
        return count($this->nextRow);
    }

    /**
     * Return the location of the item in the sort order. If the item was specified
     * in the defaults then its location is returned. If the item was not in the
     * defaults then it will be placed at the next available location. Items
     * are layed out left to right top to bottom.
     *
     * @param mixed $itemId the identifier for the item
     * @return array(string, int) an array containing a string encoded index
     *       that uniqely identifies the item and its relative position and the
     *       column index.
     */
    public function getLocation($itemId)
    {
        if (!isset($this->settings[$itemId])) {
            $this->settings[$itemId] = array($this->nextRow[$this->nextColumn], $this->nextColumn);

            $this->nextRow[$this->nextColumn] += 1;
            $this->nextColumn = ($this->nextColumn + 1) % $this->getColumnCount();
        }

        return array(
            sprintf("%08X%08x", $this->settings[$itemId][1], $this->settings[$itemId][0]) . $itemId,
            $this->settings[$itemId][1]
        );
    }

    /**
     * return whether an item has configured layout settings
     * @param $itemId the identifier for the item
     * @return boolean whether the item has a layout setting
     */
    public function hasLayout($itemId)
    {
        return isset($this->settings[$itemId]);
    }
}
