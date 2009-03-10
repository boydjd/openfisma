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
 * A YUI button for submitting forms
 *
 * @package   Yui
 * @subpackage Yui_Form
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 */
class Yui_Form_Button_Submit extends Yui_Form_Button
{
    private $_href;

    /**
     * Constructor
     */
     function __construct($label, $id)
     {
         parent::__construct($label, $id);
     }

     function render() 
     {
         // When readOnly, we need to pass the configuration item "disabled: true" to the YUI button constructor
         $disabled = $this->readOnly ? 'true' : 'false';
/*         $render = "<script type='text/javascript'>
                    YAHOO.util.Event.onDOMReady(function () {
                        var {$this->_id} = new YAHOO.widget.Button(\"{$this->_id}\", {disabled: $disabled});
                    });
                    </script><input type='submit' id='{$this->_id}' name='{$this->_id}' value='{$this->_label}'>"; */
        $render = "<script type='text/javascript'>
                   YAHOO.util.Event.onDOMReady(function () {
                       var {$this->_id} = new YAHOO.widget.Button({
                           type: \"submit\",
                           label: \"{$this->_label}\",
                           id: \"{$this->_id}\",
                           name: \"{$this->_id}\",
                           value: \"$this->_label\",
                           container: \"{$this->_id}Container\",
                           disabled: $disabled
                       });
                   });
                   </script><span id=\"{$this->_id}Container\"></span>";

         return $render;
     }
}