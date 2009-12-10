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
 * An object which represents a menu in YUI
 * 
 * @author     Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Yui
 * @version    $Id$
 */
class Fisma_Yui_Menu
{
    /**
     * Holds menu title
     * 
     * @var string
     */
    public $text;
    
    /**
     * Stores submenu id and submenu items
     * 
     * @var array
     */
    public $submenu = array();
    
    /**
     * To perform initialization as a default constructor
     * 
     * @param string $title The specified menu title
     * @return void
     */
    function __construct($title) 
    {
        $this->text = $title;
        $this->submenu['id'] = $title;
        $this->submenu['itemdata'] = array();
    }
    
    /**
     * Append a specified submenu item to the submenu data array of the menu
     * 
     * @param Fisma_Yui_MenuItem $item The specified submenu item
     * @return void
     * @todo is it necessary to support Fisma_Yui_Menu based cascading menu in the future?
     */
    function add($item) 
    {
        $this->submenu['itemdata'][] = $item;
    }
}
