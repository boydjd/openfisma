<?php
/**
 * Copyright (c) 2011 Endeavor Systems, Inc.
 *
 * This file is part of OpenFISMA.
 *
 * OpenFISMA is free software: you can redistribute it and/or modify it under the terms of the GNU General Public 
 * License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later
 * version.
 *
 * OpenFISMA is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied 
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more 
 * details.
 *
 * You should have received a copy of the GNU General Public License along with OpenFISMA.  If not, see 
 * {@link http://www.gnu.org/licenses/}.
 */

/**
 * A PHP wrapper for the jqPlot javascript wrapper chart library. 
 * 
 * These charts can asynchronously load json information from an external source or be initialized with the data 
 * in which will define how the chart will be created and what it will plot.
 * 
 * @author     Dale Frey
 * @copyright  (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Chart
 */
class Fisma_Chart
{
    /**
     * An array that holds information defining how the chart will be constructed and what it will plot
     * The array fields are as follows:
     *     Obj['chartData']       Array to pass to jqPlot as the data to plot (numbers).
     *     Obj['chartDataText']   Array of labels (strings) for each data set in chartData
     *     Obj['chartLayerText']  Array of labels (strings) for each different line/layer in a milti-line-char or 
                                  stacked-bar-chart
     *     Obj['links']           (optional) Array of links of which the browser should navigate to when a given data 
           element is clicked on
     *     Obj['linksdebug']      (optional) Boolean, if set true, an alert box of what was clicked on will pop up 
                                  instead of browser navigation based on Obj['links']
     *
     * @var Array
     */
    public $chartParamArr;
    
    /**
     * Initalizes an internal array which will be passed down to the JavaScript jqPlot-wrapper
     * 
     * @param width - width in pixels to set the chart
     * @param height - height in pixels to set the chart
     * @param externalDataURL - (optional) external data source for the JavaScript to request a Fisma_Chart->export()
     */
    public function __construct($width = null, $height = null, $chartUniqueId = null, $externalDataUrl = null)
    {
        $this->chartParamArr['chartData'] = array();
        $this->chartParamArr['chartDataText'] = array();
        $this->chartParamArr['widgets'] = array();
        $this->chartParamArr['links'] = array();
        $this->inheritanceControle('minimal');
        $this->setAlign('center');
        
        if (!empty($width)) {
            $this->setWidth($width);
        }
        
        if (!empty($height)) {
            $this->setHeight($height);
        }
        
        if (!empty($chartUniqueId)) {
            $this->setUniqueid($chartUniqueId);
        }
        
        if (!empty($externalDataUrl)) {
            $this->setExternalSource($externalDataUrl);
        }
        
        return $this;
    }
    
    /**
     * The chart title to show above the chart
     * 
     * @return Fisma_Chart
     */
    public function setTitle($inString)
    {
        $this->chartParamArr['title'] = $inString;
        return $this;
    }
    
    /**
     * The chart type (bar, pie, or stackedbar)
     * 
     * @return Fisma_Chart
     */
    public function setChartType($inString)
    {
        $inString = strtolower($inString);
        if (
            $inString !== 'bar' && 
            $inString !== 'pie' &&
            $inString !== 'stackedbar' &&
            $inString !== 'line' &&
            $inString !== 'stackedline'
        ) {
            throw new Fisma_Zend_Exception(
                "Invalid chart-type given to Fisma_Chart->setChartType(). Paramiter string must be either " .
                "bar, pie, stackedbar, line, or stacked line"
            );
        }
        
        $this->chartParamArr['chartType'] = $inString;
        return $this;
    }
    
    public function getChartType()
    {
        if (empty($this->chartParamArr['chartType'])) {
            throw new Fisma_Zend_Exception(
                "You cannot call Fisma_Chart->getChartType() when a chart-type has not been set yet."
            );
        }
        
        return $this->chartParamArr['chartType'];
    }
    
    /**
     * If the chart type is stacked-bar or stacked-line chart, return true
     * 
     * @return boolean
     */
    public function isStacked()
    {
        if (strpos($this->getChartType(), 'stacked') !== false) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * The chart width in pixels
     * 
     * @return Fisma_Chart
     */
    public function setWidth($inInteger)
    {
        $this->chartParamArr['width'] = $inInteger;
        return $this;
    }
    
    /**
     * Forces the canvas objects to be held in a scrollable div
     * If this is set false, the JavaScript wrapper may still place the canvases into
     * scrollable div if the width given by Fisma_Chart->setWidth() is too narrow to
     * contain the chart.
     * 
     * @return Fisma_Chart
     */
    private function setWidthAuto($inBoolean)
    {
        $this->chartParamArr['autoWidth'] = $inBoolean;
    }
    
    /**
     * The chart height in pixels
     * 
     * @return Fisma_Chart
     */
    public function setHeight($inInteger)
    {
        $this->chartParamArr['height'] = $inInteger;
        return $this;
    }
    
    /**
     * The uniqueid for this chart, name of the div in which holds the canvases
     * 
     * @return Fisma_Chart
     */
    public function setUniqueid($inString)
    {
        $this->chartParamArr['uniqueid'] = $inString;
        return $this;
    }
    
    /**
     * An array of CSS colors to use for each bar (in a bar chart), or each layer of bars (on a stacked bar-chart).
     * 
     * @return Fisma_Chart
     */
    public function setColors($inStrArray)
    {
        $this->chartParamArr['colors'] = $inStrArray;
        return $this;
    }
    
    public function setConcatColumnLabels($inBoolean)
    {
        $this->chartParamArr['concatXLabel'] = $inBoolean;
        return $this;
    }
    
    public function setExternalSource($inString)
    {
        $this->chartParamArr['externalSource'] = $inString;
        return $this;
    }
    
    public function inheritanceControle($inString)
    {
        $this->chartParamArr['inheritCtl'] = $inString;
        return $this;
    }
    
    public function setBorderVisibilityLeft($inBoolean)
    {
        $this->_setBorderTag('L', $inBoolean);
        return $this;
    }
    
    public function setBorderVisibilityRight($inBoolean)
    {
        $this->_setBorderTag('R', $inBoolean);
        return $this;
    }
    
    public function setBorderVisibilityTop($inBoolean)
    {
        $this->_setBorderTag('T', $inBoolean);
        return $this;
    }
    
    public function setBorderVisibilityBottom($inBoolean)
    {
        $this->_setBorderTag('B', $inBoolean);
        return $this;
    }
    
    private function _setBorderTag($tagLetter, $borderVisible)
    {
        if (empty($this->chartParamArr['borders'])) {
            $b = '';
        } else {
            $b = $this->chartParamArr['borders'];
        }
        
        // remove tag (remove from visibility list
        $b = str_replace($tagLetter, '', $b);

        // add tag if needed
        if ($borderVisible === true) {
            $b .= $tagLetter;
        }
        
        $this->chartParamArr['borders'] = $b;
    }
    
    public function setAxisLabelX($inString)
    {
        $this->chartParamArr['AxisLabelX'] = $inString;
        return $this;
    }
    
    public function setAxisLabelY($inString)
    {
        $this->chartParamArr['AxisLabelY'] = $inString;
        return $this;
    }
    
    /**
     * Sets the style for the nummbers floating ontop of the bars in stacked-bar and bar charts
     * Inside the system this will wrap each floating number with <span style="YOURINPUT">~</span>
     * Example of use: Fisma_Chart->setFloatingNumbersFontStyle('color: green; font-size: 10pt; font-weight: bold');
     * The default value is 'color: black; font-size: 12pt; font-weight: bold' set by the JavaScript wrapper
     * If set empty with setFloatingNumbersFontStyle(''), the default will fall back to jqPlot lib's default
     *
     * @return Fisma_Chart
     */
    public function setFloatingNumbersFontStyle($innerStyleCodeCss)
    {
        $this->chartParamArr['pointLabelStyle'] = $innerStyleCodeCss;
    }

    /**
     * For use with stacked charts. This will inform Fisma_Chart how many layers on the stacked-chart there will be
     * Or basically, how many rows are in each column.
     * WARNING: This will initalize (and erase) any plot-data (numerical) previously added with addColumn or setData
     * 
     * @return Fisma_Chart
     */
    public function setLayerCount($inInteger) {
        if ($inInteger < 1) {
            return $this;
        }
        for ($l = 0; $l < count($inInteger); $l++) {
            $this->chartParamArr['chartData'][] = array();
        }
        
        return $this;
    }
    
    public function setAlign($inString)
    {
        $this->chartParamArr['align'] = $inString;
        return $this;
    }
    
    public function setColumnLabelAngle($inInteger)
    {
        $this->chartParamArr['DataTextAngle'] = $inInteger;
        return $this;
    }
    
    /**
     * Adds a column onto the chart to render. The input params may either be a value (bar/pie chart), or an array of
     * values (stacked-bar/stacked-line chart).
     * 
     * @return Fisma_Chart
     */
    public function addColumn($columnLabel, $addValue, $addLink = null)
    {
        // Do not add null values
        if (empty($addValue)) {
            $addValue = 0;
        }
        
        // We must know the chart type in order to know howto format the data in the data array
        if (empty($this->chartParamArr['chartType'])) {
            throw new Fisma_Zend_Exception(
                "You cannot call Fisma_Chart->addColumn() until a chartType has been " . 
                "set with Fisma_Chart->setChartType()"
            );
        }
        
        // Columns are for bar charts
        if ($this->chartParamArr['chartType'] === 'line' || $this->chartParamArr['chartType'] === 'stackedline') {
            throw new Fisma_Zend_Exception(
                "You cannot call Fisma_Chart->addColumn() when building a line graph. Use Fisma_Chart->addLine()"
            );
        }
        
        if (!empty($addLink)) {
            if (is_string($this->chartParamArr['links'])) {
                throw new Fisma_Zend_Exception(
                    "You are trying to add a link for a certain column (in Fisma_Chart->addColumn), when you " .
                    "have already set a global link for all columns."
                );
            }
        }
        
        // If this is a pie-chart, do not add (needless) slices of 0
        if ($this->getChartType() === 'pie' && $addValue == 0) {
            return;
        }
        
        // Add label for this column
        $this->chartParamArr['chartDataText'][] = $columnLabel;
        
        // Add data to plot
        if (strpos($this->getChartType(), 'stacked') === false) {
            // This is not a stacked chart. Each data-point/column-height should be in each element of the data array
            
            $this->chartParamArr['chartData'][] = $addValue;
            $this->chartParamArr['links'][] = Fisma_String::escapeJsString($addLink, 'url');
            
        } else {
            // This is a stacked chart. Each element of the chartParamArr['chartType'] array is a layer, not a column
            
            // The input should be an array, of each data/number in this column
            if (!is_array($addValue)) {
                throw new Fisma_Zend_Exception(
                    "addValue param in Fisma_Chart->addColumn() is expected to be an array when " . 
                    "building a stacked chart."
                );
            }
            
            // We need to know the dimensions of the data array
            $layerCount = count($this->chartParamArr['chartData']);
            if ($layerCount === 0) {
                throw new Fisma_Zend_Exception(
                    "You cannot call Fisma_Chart->addColumn() with stacked chart types, until a layer-count has been ".
                    "defined with Fisma_Chart->setLayerCount(), or auto-defined with Fisma-Chart->setLayerLabels()"
                );
            }
            
            for ($layer = 0; $layer < count($addValue); $layer++) {
                $this->chartParamArr['chartData'][$layer][] = $addValue[$layer];
                
                if (!empty($addLink)) {
                    $this->chartParamArr['links'][$layer][] = Fisma_String::escapeJsString($addLink[$layer], 'url');
                }
            }
        }
        
        return $this;
    }
    
    /**
     * Returns the number of columns/bar in Fisma_Chart's data array so far.
     * 
     * @return integer
     */
    public function getColumnCount()
    {
        if (strpos($this->getChartType(), 'stacked') === false) {
            return count($this->chartParamArr['chartData']);
        } else {
            return count($this->chartParamArr['chartData'][0]);
        }
    }
    
    /**
     * Adds a line onto the chart to render. The input params should be an array of values.
     * 
     * @return Fisma_Chart
     */
    public function addLine($lineValues, $setColumnLabels = '')
    {
        // Add label for this column
        if (!empty($setColumnLabels) && $setColumnLabels !== '') {
            $this->chartParamArr['chartDataText'] = $setColumnLabels;
        }
        
        $this->chartParamArr['chartData'][] = $lineValues;
        
        return $this;
    }

    /**
     * Adds a value onto a line in a line or stacked line chart.
     * 
     * @return Fisma_Chart
     */
    public function addToLine($value, $lineIndex = 0)
    {
        $this->chartParamArr['chartData'][0][] = $value;
        
        return $this;
    }

    /**
     * Overrides, erases, and sets the data array (numbers to plot) to the array given
     * 
     * @return Fisma_Chart
     */
    public function setData($inArray)
    {
        $this->chartParamArr['chartData'] = $inArray;
        return $this;
    }
    
    /**
     * Will take a stacked bar-chart and convert it to a regualr bar chart by totalling
     * all the stacks in each column togeather.
     * WARNING: This process is not undoable.
     * NOTICE: All columns elements will loose their links from this process.
     * 
     * @return Fisma_Chart
     */
    public function convertFromStackedToRegular()
    {
        // total the layers
        
        $layers = $this->chartParamArr['chartData'];
        $newColData = array();

        for ($c = 0; $c < count($layers[0]); $c++) {
            
            $thisColumnTotal = 0;
            
            for ($l = 0; $l < count($layers); $l++) {
                $thisColumnTotal += $layers[$l][$c];
            }
            
            $newColData[$c] = $thisColumnTotal;
        }
        
        // update chart type
        $t = $this->getChartType();
        $t = str_replace('stacked', '', $t);
        $this->setChartType($t);
        
        // erase layer information (labels)
        unset($this->chartParamArr['chartLayerText']);
        
        $this->setLinks(array());
        $this->setData($newColData);
        
        return $this;
    }    
    
    /**
     * Overrides, erases, and sets the link array (or string) for chart elements to link to
     * 
     * @return Fisma_Chart
     */
    public function setLinks($inArray)
    {
        // Escape URL(s)
        if (is_array($inArray)) {
            foreach ($inArray as &$link)
                $link = Fisma_String::escapeJsString($link, 'url');
        } else {
            $inArray = Fisma_String::escapeJsString($inArray, 'url');
        }
        
        $this->chartParamArr['links'] = $inArray;
        return $this;
    }

    /**
     * When set to true, will stop chart-linking/"drill-down" and instead, show a popup message about navigation
     * 
     * @return Fisma_Chart
     */
    public function setLinksDebug($inBoolean)
    {
        $this->chartParamArr['linksdebug'] = $inBoolean;
        return $this;
    }

    /**
     * Overrides, erases, and sets the labels to use on the x-axis
     * 
     * @return Fisma_Chart
     */
    public function setAxisLabelsX($inArray)
    {
        $this->chartParamArr['chartDataText'] = $inArray;
        return $this;
    }
    
    /**
     * Turns on or off jqPlot legend visibility based on input boolean.
     * Note that this is seperate from the ThreatLegend.
     * 
     * @param boolean  
     * @return Fisma_Chart
     */
    public function setStandardLegendVisibility($inBoolean)
    {
        $this->chartParamArr['showlegend'] = $inBoolean;
        return $this;
    }
    
    /**
     * Set weather a threat-level-legend should be injected above the chart.
     * Note that this is different than the jqPlot-legend.
     * The jqPlot legend's visibility can be set with Fisma_Chart->setStandardLegendVisibility(true/false).
     * NOTICE: This will set the standard jqPlot legend's visibility to false when this function is fed true.
     * NOTICE: The colors on the threat-level-legend are static.
     * 
     * @param boolean  
     * @return Fisma_Chart
     */
    public function setThreatLegendVisibility($inBoolean)
    {
        $this->chartParamArr['showThreatLegend'] = $inBoolean;
        
        if ($inBoolean === true) {
        
            $this->setThreatLegendWidth('100%');
        
            // hide the jqPlot legen since we are injecting our own (handeled in the JavaScript wrapper)
            $this->setStandardLegendVisibility(false);
        }
        
        return $this;
    }
    
    /**
     * Controles the width of the red-orange-yellow threat-level legend above the chart when it is show
     * By default this is set to "100%", for wide charts this may be an eyesore.
     * 
     * @param string  
     * @return Fisma_Chart
     */
    public function setThreatLegendWidth($inWidth)
    {
        // pass this to the JavaScript wrapper on export
        $this->chartParamArr['threatLegendWidth'] = $inWidth;
        
        return $this;
    }
        
    /**
     * Overrides, erases, and sets the labels to use on for the different layers of bars on a stacked bar/line chart
     * 
     * @return Fisma_Chart
     */
    public function setLayerLabels($inArray)
    {
        $this->setLayerCount(count($inArray));
        $this->chartParamArr['chartLayerText'] = $inArray;
        return $this;
    }
    
    /**
     * On a stacked-bar or stacked-line chart removes a row/line from the chart
     * NOTICE: When removing a layer, the layer array is obviously reindexed. This means
     * if you do Fisma_Chart->deleteLayer(0), what WAS layer 1, is now 0.
     * If you were to do a Fisma_Chart->deleteLayer(0)->deleteLayer(1), in the end
     * you have actully deleted what initally was layers 1 and 3 before the deletion.
     *
     * @return Fisma_Chart
     */
    public function deleteLayer($layerNumber)
    {
        unset($this->chartParamArr['chartData'][$layerNumber]);
        unset($this->chartParamArr['chartLayerText'][$layerNumber]);
        
        if (!empty($this->chartParamArr['links'])) {
            
            if (!empty($this->chartParamArr['links'][$layerNumber])) {
                unset($this->chartParamArr['links'][$layerNumber]);
            }
            
        }
        
        // bug killer
        $this->chartParamArr['chartData'] = array_values($this->chartParamArr['chartData']);
        $this->chartParamArr['chartLayerText'] = array_values($this->chartParamArr['chartLayerText']);
        $this->chartParamArr['links'] = array_values($this->chartParamArr['links']);
        
        return $this;
    }
    
    /**
     * Adds a widget/option-field onto the chart
     * 
     * @param uniqueid - an optional name for the widget, the name will also be used to save a cookie to retain the
     *                   widget infomration on next load.
     * @param label - the label to show on the user interface next to this widget/chart-option
     * @param type - should be either 'text' or 'combo', to place a textbox, or drop-down/list-box below the chart
     * @param defaultvalue - the value to set the textbox or list-box to if there is no previous saved cookie-setting
     * @param cmboOpts - An array of strings supplied to show as options in a list-box
     * @return Fisma_Chart
     */
    public function addWidget($uniqueid = null, $label = null, $type = 'text', $defaultvalue = null, $cmboOpts = null)
    {
        if ($type !== 'text' && $type !== 'combo') {
            throw new Fisma_Zend_Exception(
                "Unknown widget type in Fisma_Chart->addWidget(). Type must be either 'text' or 'combo'"
            );
        }
        
        $wigData = array('type' => $type);
        
        if (!empty($uniqueid)) {
            $wigData['uniqueid'] = $uniqueid;
        }
        
        if (!empty($label)) {
            $wigData['label'] = $label;
        }
        
        if (!empty($defaultvalue)) {
            $wigData['defaultvalue'] = $defaultvalue;
        }
        
        if (!empty($cmboOpts)) {
            if (is_array($cmboOpts) === false) {
                throw new Fisma_Zend_Exception(
                    'cmboOpts paramiter in Fisma_Chart::addWidget should be an array of strings to appear in a ' .
                    'list-box (if not empty/null)'
                );
            }
            $wigData['options'] = $cmboOpts;
        }
        
        $this->chartParamArr['widgets'][] = $wigData;
        
        return $this;
    }

    /**
     * Render the chart to HTML
     * 
     * @return string
     */
    public function export($expMode = 'html')
    {
        switch ($expMode)
        {
        case 'array':
            return $this->chartParamArr;

        case 'html':

            $dataToView = array();
            $view = Zend_Layout::getMvcInstance()->getView();

            // make up a uniqueid is one was not given
            if (empty($this->chartParamArr['uniqueid'])) {
                $this->chartParamArr['uniqueid'] = 'chart' . uniqid();
            }

            // alignment html to apply to the div that will hold the chart canvas
            if (empty($this->chartParamArr['align']) || $this->chartParamArr['align'] == 'center' ) {
                
                $this->setAlign('center');
                
                $dataToView['divContainerArgs'] = 'style="' . 
                    'text-align: left; ' .
                    'margin-left: auto; ' .
                    'margin-right: auto; ' .
                    'display:none;"';

            } elseif ($this->chartParamArr['align'] == 'left' || $this->chartParamArr['align'] == 'right' ) {

                $dataToView['divContainerArgs'] = 
                    'class="' . $this->chartParamArr['align'] . '; display:none;" style="text-align: left;"';

            }

            // send the chart data to the view script as well
            $dataToView['chartParamArr'] = $this->chartParamArr;
            $dataToView['chartId'] = $this->chartParamArr['uniqueid'];

            return $view->partial('chart/chart.phtml', 'default', $dataToView);

        default:

            throw new Fisma_Zend_Exception(
                "Unknown export-mode (expMode) given to Fisma_Chart->export(). Given mode was: " . $expMode
            );
        }
    }
    
}
