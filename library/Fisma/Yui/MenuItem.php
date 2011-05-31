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
 * An object which represents a menuitem in YUI
 * 
 * @author     Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Yui
 * @version    $Id$
 */
class Fisma_Yui_MenuItem
{
    /**
     * The title of menu item which is named according to the YUI expected format. 
     * For simplicity, it is declared public.
     * 
     * @var string
     */
    
    public $text;
    
    /**
     * The URL of menu item
     * 
     * @var string
     */
    public $url;

    /**
     * The JavaScript function to be executed when this menu item is clicked.
     * 
     * @var string
     */
    public $onclick;
    
    /**
     * Performs initialization as default constructor
     * 
     * @param string $itemTitle The specified title of menu item
     * @param string $itemUrl The specified URL of menu item
     * @param Fisma_Yui_MenuItem_OnClick $onclick Optional onclick event handler.
     * @return void
     */
    function __construct($itemTitle, $itemUrl, Fisma_Yui_MenuItem_OnClick $onclick = null) 
    {
        $this->text = $itemTitle;
        $this->url = $itemUrl;
        $this->onclick = $onclick;
    }
}
