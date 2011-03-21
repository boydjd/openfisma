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
 * @subpackage Fisma_Doctrine_Record
 * @version    $Id$
 */
abstract class Fisma_Doctrine_Record extends Doctrine_Record
{
    /**
     * Store the original values of this record.
     * 
     * @var array
     */
    private $_originalValues = array();
    
    /**
     * Customized validation error messages. 
     * 
     * The keys are the names of errors as returned by Doctrine, e.g. 'email' corresponds to Doctrine_Validator_Email.
     * 
     * The values are the error messages which are displayed to the end user. These messages contain specifiers for
     * including contextual information in the error message.
     * 
     * '%f' is a specifier which is expanded to a user-friendly name for the *field* which generated the error. 
     * 
     * '%v' is a specifier which is expanded to the *value* of the field which failed validation. These two specifiers 
     * can be used to create very useful error messages for displaying to the end user.
     * 
     * @var array
     * @see _getCustomValidationErrorMessage()
     */
    private $_customValidationErrorMessage = array(
        'country' => '%f does not a valid country code (%v)',
        'date' => '%f contains an invalid date (%v)',
        'Fisma_Doctrine_Validator_Ip' => '%f is not a valid IPv4 or IPv6 address (%v)',
        'Fisma_Doctrine_Validator_Url' => '%f is not a valid URL',
        'future' => '%f must be a future date (%v)',
        'email' => '%f does not contain a valid e-mail address (%v)',
        'notblank' => '%f is required',
        'notnull' => '%f is required',
        'past' => '%f must be a past date (%v)',
        'readonly' => '%f is a read-only field',
        'time' => '%f is not a valid time value (%v)',
        'timestamp' => '%f is not a valid timestamp value (%v)',
        'unique' => 'An object already exists with the same %f',
        'usstate' => '$f does not contain a valid U.S. state code (%v)'
    );

    /**
     * Array of pending links in format alias => keys to be executed after save
     *
     * @var array $_pendingLinks
     */
    protected $_pendingLinks = array();

    /**
     * override Doctrine_Record->link() so as to store pendingLinks
     *
     * @param string $alias     related component alias
     * @param array $ids        the identifiers of the related records
     * @param boolean $now      wether or not to execute now or set pending
     * @return Doctrine_Record  this object (fluent interface)
     */
    public function link($alias, $ids, $now = false)
    {
        foreach ($ids as $id) {
            $this->_pendingLinks[$alias][$id] = true;
        }

        parent::link($alias, $ids, $now);
    }

    /**
     * returns Doctrine_Record instances which need to be linked (adding the relation) on save
     * 
     * @return array $pendingLinks 
     */
    public function getPendingLinks()
    {
        return $this->_pendingLinks;
    }
        
    /**
     * Get an array of modified fields with their original values
     * 
     * @param string $fieldName
     * @return mixed May return null if no original value was captured
     */
    public function getOriginalValue($fieldName)
    {
        return array_key_exists($fieldName, $this->_originalValues) ? $this->_originalValues[$fieldName] : null;
    }
    
    /**
     * Hook into the setter. This is the only place where we can see all data.
     */
    protected function _set($fieldName, $value, $load = true)
    {
        parent::_set($fieldName, $value, $load);
        
        if (!array_key_exists($fieldName, $this->_originalValues) && array_key_exists($fieldName, $this->_oldValues)) {
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
                    /**
                     * Custom validators are returned as objects, not strings. So we need to convert objects into 
                     * strings (by class name) so that a validation message can be generated
                     */
                    if (is_object($error)) {
                        $errorName = get_class($error);
                    } else {
                        $errorName = $error;
                    }
                    
                    $message = $this->_getCustomValidationErrorMessage($errorName, $field)
                             . "\n";
                }
            }
            
            return $message;
        } else {
            return false;
        }
    }

    /**
     * Override parent setup 
     * 
     * @access public
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $this->addListener(new ReplaceInvalidCharactersListener(), 'ReplaceInvalidCharactersListener');
        $this->addListener(new XssListener(), 'XssListener');
    }
    
    /**
     * Get a customized error validation message that is suitable for displaying to an end user
     * 
     * @see _customValidationErrorMessage
     * 
     * @param string $error Doctrine's name for the validation error
     * @param string $field The name of the field which failed validation
     * @return string
     */
    
    private function _getCustomValidationErrorMessage($error, $field)
    {
        // Include the logical name in the error message, or else use the physical name
        $columnName = $this->getTable()->getColumnName($field);
        $column = $this->getTable()->getColumnDefinition($columnName);

        if (isset($column['extra']['logicalName'])) {
            $userFriendlyName = $column['extra']['logicalName'];
        } else {
            $userFriendlyName = $field;
        }
        
        // Lookup the value which failed
        $invalidValue = $this->$field;

        if (isset($this->_customValidationErrorMessage[$error])) {
            // Get the error message from the array and do string substitution on the specifiers embedded in the string
            $errorTemplate = $this->_customValidationErrorMessage[$error];
            
            $specifiers = array('%f', '%v');
            $specifierExpansions = array($userFriendlyName, $invalidValue);
            
            $errorMessage = str_replace($specifiers, $specifierExpansions, $errorTemplate);
        } else {
            $errorMessage = "$userFriendlyName failed a validation: $error";
        }
        
        return $errorMessage;
    }

    /**
     * Returns a reference to the default cache
     * 
     * @access protected 
     * @return void
     */
    protected function _getCache()
    {
        $bootstrap = (Zend_Controller_Front::getInstance()->getParam('bootstrap')) ? Zend_Controller_Front::getInstance()->getParam('bootstrap') : false;

        return ($bootstrap) ? $bootstrap->getResource('cachemanager')->getCache('default') : null;
    }
}
