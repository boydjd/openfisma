<?php
/**
 * Copyright (c) 2008 Endeavor Systems, Inc.
 *
 * This file is part of OpenFISMA.
 *
 * OpenFISMA is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * OpenFISMA is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with OpenFISMA.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 * @version   $Id$
 */

/**
 * A YUI button that acts as a link (anchor tag: <a>)
 *
 * @package   Yui
 * @subpackage Yui_Form
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 */
class Yui_Form_Button_Link extends Yui_Form_Button
{    
    private $_href;
    private $_image;
    private $_onClick;
    
    /**
     * Constructor
     */
    function __construct($label, $id, $href = '#', $image = null)
    {
        parent::__construct($label, $id);
        $this->_href = $href;
        if (isset($image)) {
            $this->_image = $image;
        }
    }
     
    function render() 
    {
        $onClick = (!empty($this->_onClick)) ? "onclick: {fn: $this->_onClick}," : '';
        $render = "<span id='{$this->_id}'></span>
                   <script type='text/javascript'>
                        var button = new YAHOO.widget.Button({  
                             type: \"link\",  
                             label: \"{$this->_label}\",  
                             href: \"{$this->_href}\",
                             id: \"{$this->_id}Button\",
                             $onClick  
                             container: \"{$this->_id}\"
                        });";
        if (isset($this->_image)) {
            $render .= "button._button.style.background = 'url($this->_image) 10% 50% no-repeat';\n";
            $render .= "button._button.style.paddingLeft = '3em';\n";
        }
        $render .= "\n</script>";
        return $render;
    }
    
    function onClick($functionName) {
        $this->_onClick = $functionName;
    }
}