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
 * An object which represents a tooltip in YUI
 *
 * @author     Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Yui
 */
class Fisma_Yui_Tooltip
{
    /**
     * The unique ID of the tooltip in DOM
     * 
     * @var string
     */
    private $_id;
    
    /**
     * The title of the tooptip
     * 
     * @var string
     */
    private $_title;
    
    /**
     * Tip content
     * 
     * @var string
     */
    private $_content;
    
    /**
     * Create a new tooltip
     * 
     * @param string $id A unique ID for this page to represent the tooltip in the DOM
     * @param string $title The label for the tooltip
     * @param string $content The content displayed when the tooltip is displayed
     * @return void
     */
    function __construct($id, $title, $content) 
    {
        $this->_id = $id;
        $this->_title = $title;
        $this->_content = $content;
    }

    /**
     * Returns the HTML snippet of this rendered tooltip
     * 
     * @return string An HTML rendering of the tooltip
     */
    function __toString() 
    {
        // Wrap the text in <p> tags if none exist
        $content = $this->_content;
        if (!strpos($content, '<p>')) {
            $content = "<p>$content</p>";

        }
        
        // Render the tooltip
        $render = "<span id='{$this->_id}Tooltip' class='tooltip'>$this->_title</span><script type='text/javascript'>"
                . "var {$this->_id}TooltipVar = new YAHOO.widget.Tooltip("
                . "\"{$this->_id}TooltipObj\", { context:\"{$this->_id}Tooltip\", "
                . "showdelay: 150, hidedelay: 150, autodismissdelay: 25000, "
                . "text:\"{$content}\", "
                . 'effect:{effect:YAHOO.widget.ContainerEffect.FADE,duration:0.25}, '
                . 'width: "50%"})</script>';
                
        return $render;
    }
}
