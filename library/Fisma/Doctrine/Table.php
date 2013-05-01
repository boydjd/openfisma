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
 * Contain table-level functions that are common to all models
 *
 * @uses Doctrine_Table
 * @package Fisma
 * @subpackage Fisma_Doctrine_Table
 * @copyright (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @author Josh Boyd <joshua.boyd@endeavorsystems.com>
 * @license http://www.openfisma.org/content/license GPLv3
 */
abstract class Fisma_Doctrine_Table extends Doctrine_Table
{
    /**
     * Custom logicalName's that would amend YML column definition
     */
    protected $_customLogicalNames = array();
    protected $_editableFields = array();
    protected $_viewUrl;

    /**
     * Return logicalName for a column from hard-coded array or YML column definition
     *
     * @return String
     */
    public function getLogicalName($fieldName)
    {
        if (array_key_exists($fieldName, $this->_customLogicalNames)) {
            return $this->_customLogicalNames[$fieldName];
        }

        $columnDef = $this->getColumnDefinition($this->getColumnName($fieldName));
        if (isset($columnDef['extra']) && isset($columnDef['extra']['logicalName'])) {
            return $columnDef['extra']['logicalName'];
        }

        return $this->getColumnName($fieldName);
    }

    /**
     * Return comment/tooltip for a column from YML column definition
     *
     * @return String
     */
    public function getComment($fieldName)
    {
        $columnDef = $this->getColumnDefinition($this->getColumnName($fieldName));
        if (isset($columnDef['comment'])) {
            return $columnDef['comment'];
        }

        return '';
    }

    /**
     * Return the list of editable fields in assoc-array format ('field' => 'logicalName')
     *
     * @return array
     */
    public function getEditableFields()
    {
        $result = array();
        foreach ($this->_editableFields as $field) {
            $result[$field] = $this->getLogicalName($field);
        }
        return $result;
    }

    /**
     * Validates a given field using table ATTR_VALIDATE rules.
     * @see Doctrine::ATTR_VALIDATE
     *
     * @param string $fieldName
     * @param string $value
     * @param Doctrine_Record $record   record to consider; if it does not exists, it is created
     * @return Fisma_Doctrine_Validator_ErrorStack $errorStack
     */
    public function validateField($fieldName, $value, Doctrine_Record $record = null)
    {
        if ($record instanceof Doctrine_Record) {
            $errorStack = $record->getErrorStack();
        } else {
            $record  = $this->create();
            $errorStack = new Doctrine_Validator_ErrorStack($this->getOption('name'));
        }

        if ($value === self::$_null) {
            $value = null;
        } else if ($value instanceof Doctrine_Record && $value->exists()) {
            $value = $value->getIncremented();
        } else if ($value instanceof Doctrine_Record && ! $value->exists()) {
            foreach ($this->getRelations() as $relation) {
                if ($fieldName == $relation->getLocalFieldName() && (get_class($value) == $relation->getClass()
                    || is_subclass_of($value, $relation->getClass()))) {
                    return $errorStack;
                }
            }
        }

        $dataType = $this->getTypeOf($fieldName);

        // Validate field type, if type validation is enabled
        if ($this->getAttribute(Doctrine::ATTR_VALIDATE) & Doctrine::VALIDATE_TYPES) {
            if ( ! Doctrine_Validator::isValidType($value, $dataType)) {
                $errorStack->add($fieldName, 'type');
            }
            if ($dataType == 'enum') {
                $enumIndex = $this->enumIndex($fieldName, $value);
                if ($enumIndex === false && $value !== null) {
                    $errorStack->add($fieldName, 'enum');
                }
            }
        }

        // Validate field length, if length validation is enabled
        if ($this->getAttribute(Doctrine::ATTR_VALIDATE) & Doctrine::VALIDATE_LENGTHS) {
            if ( ! Doctrine_Validator::validateLength($value, $dataType, $this->getFieldLength($fieldName))) {
                $errorStack->add($fieldName, 'length');
            }
        }

        // Run all custom validators
        foreach ($this->getFieldValidators($fieldName) as $validatorName => $args) {
            if ( ! is_string($validatorName)) {
                $validatorName = $args;
                $args = array();
            }

            // Use  Fisma_Doctrine_Validator which suppress warning message of FileNotFound
            $validator = Fisma_Doctrine_Validator::getValidator($validatorName);
            $validator->invoker = $record;
            $validator->field = $fieldName;
            $validator->args = $args;
            if ( ! $validator->validate($value)) {
                $errorStack->add($fieldName, $validator);
            }
        }

        return $errorStack;
    }

    public function getViewUrl()
    {
        return (empty($this->_viewUrl) ? '/' . $this->getComponentName() . '/view/id/' : $this->_viewUrl);
    }
}
