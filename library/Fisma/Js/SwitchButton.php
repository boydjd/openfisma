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
 * A switch button is a toggle button with ON and OFF states
 * 
 * This is a wrapper to SwitchButton.js
 *
 * @author     Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Controller
 */
class Fisma_Js_SwitchButton
{
    /**
     * An identifier which is unique to the current page
     * 
     * @var int
     */
    private $_id;
    
    /**
     * The initial state of the switch. True => ON, false => OFF
     * 
     * @var bool
     */
    private $_initialState;
    
    /**
     * Name of a javascript callback method to be called when this button's state changes
     */
    private $_callbackMethod;
    
    /**
     * An arbitrary array of parameters which will get passed to the javascript click handler for this button
     */
    private $_payload;
    
    /**
     * Create a new switch button instance
     * 
     * @param int $id An identifier which is unique to the current page
     * @param bool $initialState True if the switch defaults to ON, false if it defaults to OFF
     * @param string $callbackMethod (Optional) The name of a Javascript callback method to be called when the 
     * button's state changes. See SwitchButton.js for details.
     * @param array $payload (Optional) An arbirtrary array which can be used to pass any necessary information to the 
     * javascript handler for button click events.
     */
    public function __construct($id, $initialState = true, $callbackMethod = null, $payload = null)
    {
        $this->_id = $id;
        $this->_initialState = $initialState;
        $this->_callbackMethod = $callbackMethod;
        $this->_payload = $payload;
    }
    
    /**
     * Render button to string including HTML markup and javascript
     */
    public function __tostring()
    {        
        // Prepare arguments to the switch button constructor.
        $initialState = $this->_initialState ? 'true' : 'false';
        $callback = isset($this->_callbackMethod) ? ", '$this->_callbackMethod'" : '';
        $payload = isset($this->_payload) ? (', ' . json_encode($this->_payload)) : '';
        
        $constructorArguments = "'$this->_id', $initialState $callback $payload";

        $render = "<div id='$this->_id'></div>
                   <script type='text/javascript'>
                       YAHOO.util.Event.onDOMReady(function () {
                           var switchButton = new Fisma.SwitchButton($constructorArguments);
                       });
                   </script>";
        
        return $render;
    }
}