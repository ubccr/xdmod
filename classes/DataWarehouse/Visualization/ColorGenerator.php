<?php
namespace DataWarehouse\Visualization;

/*
 * @author Joe White
 * @author Jeanette Sperhac
 *
 * Class that governs color selection for plotting datasets in HighCharts classes.
 * Adapted for use with configure() method. Lookup is by index; round robin only.
 * Todo: resuscitate fixed mapping, which is constructed to work with dataset names.
 *
 */

class ColorGenerator
{
    // If the number of datasets is below the threhold, then the mapping
    // is a fixed map of dataset name -> dataset color. If the number of
    // datasets is above the threshold, then a simple first-come first-served
    // mapping is used.
    const COLOR_MAP_THRESHOLD = 64;

    private $colors = array();
    private $color_idx = null;
    private $limit_color = null;
    private $mode = null;

    // ---------------------------------------------------------
    // @param configColor, hexdec color value from the colors array.
    // create the colors array, setting current index as user selected color for dataset.
    // backward compatible to Usage tab, for now.
    //
    //public function __construct( $configColor=null )
    // ---------------------------------------------------------
    public function __construct(
        $datanamevalues = null,
        $limit = null,
        $useShortNames = null,
        $configColor = null
    ) {
    
        $this->colors = array();
        $this->color_idx = null;
        $this->limit_color = $this::COLOR_MAP_THRESHOLD;

        $this->build_roundrobinmapping();

        // if configColor is null, color idx is 0
        $this->setConfigColor($configColor);
    }
    
    // ---------------------------------------------------------
    // getConfigColor()
    //
    // Assign configColor and return it.
    //
    // if user has selected a starting color for the dataseries,
    // set it as the initial color for the plot.
    // ---------------------------------------------------------
    public function getConfigColor($configColor)
    {
        $this->setConfigColor($configColor);
        $colorVal = $this->getColorByIdx($this->color_idx);
        return $colorVal;
    }

    // ---------------------------------------------------------
    // setConfigColor()
    //
    // if user has selected a starting color for the dataseries,
    // set it as the initial color for the plot.
    // ---------------------------------------------------------
    public function setConfigColor($configColor)
    {
        // if configColor is null, color idx is 0
        if (is_null($configColor)) {
            $this->color_idx = 0;
        } else {
            // If configColor found in colors array, set that as current idx.
            $si = array_search($configColor, $this->colors);
            $this->color_idx = ($si > -1)
                                ? $si
                                : 0; // default 0
        }
        //return $this->color_idx;
    }

    // ---------------------------------------------------------
    // getColorByIdx()
    //
    // Specify color_idx; look up and return resulting color from
    // colors array.
    // ---------------------------------------------------------
    private function getColorByIdx($idx)
    {
        if ($this->mode == "ROUND_ROBIN") {
            $color = $this->colors[ $idx % count($this->colors) ];
            $this->color_idx = ++$idx;
            return $color;
        } else // fixed mapping
        {
            if (isset($this->colors[$idx])) {
                return $this->colors[$idx];
            } else {
                return $this->limit_color;
            }
        }
    }
        
    // ---------------------------------------------------------
    // build_roundrobinmapping()
    //
    // color_idx is set in constructor based on user selection
    // ---------------------------------------------------------
    private function build_roundrobinmapping()
    {
        $this->mode = "ROUND_ROBIN";
        $this->colors = \DataWarehouse\Visualization::getColors(null, 0, false);
    }

    // ---------------------------------------------------------
    // build_fixedmapping()
    //
    // For fixed number of values in dataset. Not presently in use.
    // ---------------------------------------------------------
    private function build_fixedmapping(&$datanamevalues, $limit, $useShortNames)
    {
        $this->mode = "FIXED_MAP";

        $data_series_count = count($datanamevalues);

        $color_count = $data_series_count;
        if ($data_series_count - $limit > 1) {
            // One extra color for the 'limit' dataset
            $color_count += 1;
        }

        $colors = \DataWarehouse\Visualization::getColors($color_count, 0, false);

        $datalabels = array();
        $dataoveralls = array();


        foreach ($datanamevalues as $key => $row) {
            $datalabels[$key] = $row['name'];
            $dataoveralls[$key] = $row['value'];
        }
        array_multisort($datalabels, SORT_DESC, $dataoveralls, SORT_DESC, $datanamevalues);

        $mapval = $useShortNames ? 'short_name' : 'name';

        foreach ($datanamevalues as $key => $datalabel_to_overall) {
            $this->colors[$datalabel_to_overall[$mapval]] = $colors[$key];
        }

        if ($color_count > 0) {
            $this->limit_color = $colors[ $color_count - 1 ];
        }
    }

    // ---------------------------------------------------------
    // getColor()
    //
    // Fetch and return current color; increment color index.
    //
    // @param string
    // default null returns current color for round robin mapping
    // ---------------------------------------------------------
    public function getColor($dataseriesname = null)
    {
        if ($this->mode == "ROUND_ROBIN" || is_null($dataseriesname)) {
            $color = $this->colors[ $this->color_idx % count($this->colors) ];
            $this->color_idx += 1;
            return $color;
        } else {
            if (isset($this->colors[$dataseriesname])) {
                return $this->colors[$dataseriesname];
            } else {
                return $this->limit_color;
            }
        }
    }
} // class ColorGenerator
