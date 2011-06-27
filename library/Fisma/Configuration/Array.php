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
 * A concrete implementation of the configuration interface using an array as storage
 * 
 * This implementation is ideal for testing purposes to provide an alternative to the database configuration object.
 * 
 * For a production implementation, see the Configuration model
 * 
 * @see Configuration
 * 
 * @author     Mark E. Haase
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Configuration
 */
class Fisma_Configuration_Array implements Fisma_Configuration_Interface
{
    /**
     * This array holds the configuration dictionary
     * 
     * @var array
     */
    private $_configuration;
    
    /**
     * Get a configuration item by name
     * 
     * @param string $name The specified configuration item name to obtain
     * @return mixed The value of the requested configuration item name
     * @throws Fisma_Zend_Exception_Config if the requested configuration item name is invalid
     */
    public function getConfig($name) 
    {
        return array_key_exists($name, $this->_configuration)? $this->_configuration[$name] : NULL;
    }
    
    /**
     * Set a configuration item by name
     * 
     * @param string $name The specified configuration item name to set
     * @param mixed $value The specified value of the configuration item name to set
     * @return void
     */
    public function setConfig($name, $value) 
    {
        $this->_configuration[$name] = $value;
    }
}
