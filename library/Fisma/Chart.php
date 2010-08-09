<?php
/**
 * Copyright (c) 2008 Endeavor Systems, Inc.
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
 * A wrapper for the flash chart library. 
 * 
 * These charts asynchronously load XML definition files which contain formatting and data for the chart.
 * 
 * @author     Mark E. Haase
 * @copyright  (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Chart
 * @version    $Id$
 */
class Fisma_Chart
{
    /**
     * The URL for this flash chart's XML definition
     * 
     * @var string
     */
    private $_sourceUrl;
    
    /**
     * Width of the chart
     * 
     * @var int
     */
    private $_width;
    
    /**
     * Heigh of the chart
     * 
     * @var int
     */
    private $_height;
        
    /**
     * Build a flash chart object
     * 
     * @param string $sourceUrl The URL which contains the XML definition/data for this chart
     * @param int $width Width of chart in pixels
     * @param int $height Width of chart in pixels
     */
    public function __construct($sourceUrl, $width, $height)
    {
        if (empty($sourceUrl)) {
            throw new Fisma_Zend_Exception("Source URL is required for chart");
        }
        
        if (!(is_int($width) && is_int($height))) {
            throw new Fisma_Zend_Exception(
                "Chart width and height must both be integers (width=$width, height=$height)"
            );
        }
        
        $this->_sourceUrl = $sourceUrl;
        $this->_width = $width;
        $this->_height = $height;
    }
    
    /**
     * Render the chart to an HTML string
     * 
     * @return string
     */
    public function __toString()
    {
        $view = Zend_Layout::getMvcInstance()->getView();

        $data = array(
            'sourceUrl' => $this->_sourceUrl,
            'width' => $this->_width,
            'height' => $this->_height,
            'containerId' => uniqid()
        );

        return $view->partial('chart/chart.phtml', 'default', $data);
    }
}
