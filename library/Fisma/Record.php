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
 * A subclass of Doctrine_Record which adds support for getting the original value of a field after the object has been
 * persisted.
 * 
 * Base Doctrine_Record does not provide a reliable means to do this. It has getLastModified(true) which returns
 * the value of a field, but if you update a field more than once before persisting, then you can never get the 
 * original value back.
 * 
 * @author     Mark E. Haase
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Record
 * @version    $Id$
 */
class Fisma_Record extends Doctrine_Record
{
    /**
     * Store the original values of this record.
     * 
     * @var array
     */
    private $_originalValues = array();
    
    /**
     * Get an array of modified fields with their original values
     * 
     * @param string $fieldName
     * @return mixed May return null if no original value was captured
     */
    public function getOriginalValue($fieldName)
    {
        return isset($this->_originalValues[$fieldName]) ? $this->_originalValues[$fieldName] : null;
    }
    
    /**
     * Hook into the setter. This is the only place where we can see all data.
     */
    protected function _set($fieldName, $value, $load = true)
    {
        parent::_set($fieldName, $value, $load);
        
        if (!isset($this->_originalValues[$fieldName]) && isset($this->_oldValues[$fieldName])) {
            $this->_originalValues[$fieldName] = $this->_oldValues[$fieldName];
        }
    }
    
    /**
     * Generate a better validation error message than Doctrine's default by overriding the parent class method
     *
     * @return string $message
     */
    public function getErrorStackAsString()
    {
        $errorStack = $this->getErrorStack();

        if (count($errorStack)) {
            $count = count($errorStack);
                        
            foreach ($errorStack as $field => $errors) {
                foreach ($errors as $error) {
                    
                    // Include the logical name in the error message, or else use the physical name
                    $columnName = $this->getTable()->getColumnName($field);
                    $column = $this->getTable()->getColumnDefinition($columnName);

                    if (isset($column['extra']['logicalName'])) {
                        $userFriendlyName = $column['extra']['logicalName'];
                    } else {
                        $userFriendlyName = $field;
                    }
                    
                    /**
                     * Doctrine provides unhelpful string constants to describe errors, instead of real named 
                     * constants. I also can't find anywhere that these constants are documented. So the goal here is 
                     * to trap the errors we do know and write out a sensible error message, while providing a 
                     * fallback for errors that we aren't aware of.
                     */
                    switch ($error) {
                        case 'unique':
                            $message = "An object already exists with the same $userFriendlyName";
                            break;
                        default:
                            $message = "$userFriendlyName failed a validation: $error";
                            break;
                    }
                    
                    $message .= "\n";
                }
            }
            
            return $message;
        } else {
            return false;
        }
    }
}
