<?php
/**
 * Copyright (c) 2012 Endeavor Systems, Inc.
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
 * Breadcrumbs building for OpenFISMA
 *
 * @author     Xue-Wei Tang <xue-wei.tang@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2012 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage 
 */
require_once 'KLogger.php';

class Fisma_Breadcrumbs
{
    // Breadcrumb info from the configuratin file breadcrumbs.yml.
    private $_breadcrumbInfo = null;
    

    
    /**
     * Default constructor:
     * Load the breadcrumbs info from the YAML configure file.
     *
     */
    public function __construct()
    {
        $this->_breadcrumbInfo = array();
            
        $path = Fisma::getPath('config');
        $this->_breadcrumbInfo = Doctrine_Parser_YamlSf::load($path . '/breadcrumbs.yml');
    }
    
    /**
     * Convert the tokenized string to an array.
     * 
     * @param tokenized string $str
     * @return array: 
     */
    private static function tok2Array($str)
    {
        $a = array();
    
        $tok = strtok($str, ",");
        while ($tok !== false) {
            array_push($a, $tok);
            $tok = strtok(",");
        }
        
        return $a;
    }
    
    /**
     * Return the breadcrumbs for the given URL
     *
     * @param $url the page URL 
     *
     * @return string the breadcrumb <div> tag
     */
    public function getBreadcrumbs($url)
    {
        $breadcrumbs = null;
        
        // Find the item that matches the given URL.
        $item = null;
        foreach ($this->_breadcrumbInfo as $key => $val) {
            $item = $this->_breadcrumbInfo[$key];
        
            $urlItem = $item["url"];
            if (strpos($url, $urlItem) !== false) {
                // Found matching URL
                break;
            }
        }
        
        // The given URL is not found in the breadcrumbs configuration
        if (!isset($item) || 
             strpos($url, "dashboard") !== false ||
             strpos($url, "phpinfo") !== false)
        {
            $breadcrumbs = "<div id=\"breadcrumbs\"></div>";
            return $breadcrumbs;
        }
        
        // Process item found from the breadcrumbs configuration
        $labels = $item["breadcrumb-labels"];
        $links = $item["breadcrumb-links"];
        
        $labelItems = self::tok2Array($labels);
        $linkItems  = self::tok2Array($links);
                
        // Construct the breadcrumbs
        $breadcrumbs = 
            "<div id=\"breadcrumbs\" style=\"padding: 10px 10px 0 30px; font-weight: bold; font-size: 1.0em;\">";
        for ($i=0; $i < count($labelItems); $i++){
            if ($linkItems[$i] === "no-link") {
                $breadcrumbs .= $labelItems[$i];
            }
            else {
                $breadcrumbs .= "<a href=\"$linkItems[$i]\">$labelItems[$i]</a>";
            }
            
            if ($i < count($labelItems) - 1) {
                $breadcrumbs .= "<img src=\"/images/bullet_raquo.gif\" style=\"padding: 0 10px 0 10px;\">";
            }
        }
        
        $breadcrumbs .= ":</div>";
        
        return $breadcrumbs;
    }
}
