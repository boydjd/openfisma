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
 * Listener for the object which is using this behavior
 *
 * @author     Mark E. Haase
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Doctrine_Behavior_AuditLoggable
 */
class Fisma_Doctrine_Behavior_AuditLoggable_ObjectListener extends Doctrine_Record_Listener
{
    /**
     * Default options for the listener
     *
     * @var array
     */
    protected $_options = array(
        'logCreateObject' => false,
        'logUpdateObject' => false,
        'logUpdateField' => false,
        'logDeleteObject' => false
    );

    /**
     * Create and configure a new listener
     *
     * @param array $options The object listener options
     * @return void
     */
    public function __construct($options)
    {
        $this->_options = array_merge($this->_options, $options);
    }

    /**
     * Audit log behavior for created objects
     *
     * @param Doctrine_Event $event The triggered doctrine event
     * @return void
     */
    public function postInsert(Doctrine_Event $event)
    {
        // Logs are only created if the logCreateObject option is true
        if ($this->_options['logCreateObject']) {
            $invoker = $event->getInvoker();

            $invoker->getAuditLog()->write('Created');
        }
    }

    /**
     * Audit log behavior for updated objects
     *
     * @param Doctrine_Event $event The triggered doctrine event
     * @return void
     */
    public function postUpdate(Doctrine_Event $event)
    {
        $invoker = $event->getInvoker();
        $modified = $invoker->getLastModified();

        // Handle object-level logging
        if ($this->_options['logUpdateObject']) {

            // Prepare a list of modified fields using logical names
            $fields = array();

            foreach ($modified as $field => $value) {
                $logicalName = $this->_getLogicalNameForField($invoker->getTable(), $field);
                if ($logicalName) {
                    $fields[] = $logicalName;
                }
            }

            // Write new log if any loggable fields were modified
            if (count($fields) > 0) {
                $invoker->getAuditLog()->write('Updated ' . implode(', ', $fields));
            }
        }

        // Handle field-level logging
        if ($this->_options['logUpdateField']) {
            foreach ($modified as $field => $value) {

                // Individual fields need to be marked with an extra attribute called 'auditLog' to generate individual
                // log entries and must have a 'logicalName' attribute also
                if ($this->_fieldIsLoggable($invoker->getTable(), $field)) {

                    $logicalName = $this->_getLogicalNameForField($invoker->getTable(), $field);

                    if (empty($logicalName)) {
                        throw new Exception("Field ($field) cannot be logged because it does not have a logical name");
                    }

                    // The log always shows the old and new values for the field.
                    $oldValue = $invoker->getLastModified(true);
                    $oldValue = $oldValue[$field];
                    $newValue = $invoker->$field;

                    //if the field is a foreign key and an id type, then get the name from foreign table
                    $relation = $this->_fieldHasRelation($invoker->getTable(), $field);
                    if ($relation) {
                        if ($oldValue) {
                            $oldValue = $this->_getRelationFieldName($relation, $oldValue);
                        }

                        if ($newValue) {
                            $newValue = $this->_getRelationFieldName($relation, $newValue);
                        }
                    }

                    if ($this->_fieldIsHtml($invoker->getTable(), $field)) {
                        $oldValue = Fisma_String::htmlToPlainText($oldValue);
                        $newValue = Fisma_String::htmlToPlainText($newValue);
                    }

                    if (empty($oldValue)) {
                        $oldValue = "**No value**";
                    }

                    $message = "Updated $logicalName\n\nOLD:\n$oldValue\n\nNEW:\n$newValue";
                    $invoker->getAuditLog()->write($message);
                }
            }
        }
    }

    /**
     * Audit log behavior for deleted objects
     *
     * This only makes sense for objects which have a soft delete behavior. Otherwise there is no object to create
     * logs for.
     *
     * @param Doctrine_Event $event The triggered doctrine event
     * @return void
     */
    public function postDelete(Doctrine_Event $event)
    {
        // Logs are only created if the logDeleteObject option is true
        if ($this->_options['logDeleteObject']) {
            $invoker = $event->getInvoker();

            $invoker->getAuditLog()->write('Deleted');
        }
    }

    /**
     * Determine whether a particular field on a particular table should be logged individually
     *
     * @param Doctrine_Table $table The specified doctrine table object to be checked
     * @param string $field The specified field of table to be checked
     * @return boolean True if found extra property 'auditLog' in the table definition, false otherwise
     */
    private function _fieldIsLoggable(Doctrine_Table $table, $field)
    {
        $definition = $table->getDefinitionOf($field);

        if (@isset($definition['extra']['auditLog'])) {
            return $definition['extra']['auditLog'];
        } else {
            return false;
        }
    }

    /**
     * Determine whether a particular field on a particular table contains HTML
     *
     * @param Doctrine_Table $table The specified doctrine table object to be checked
     * @param string $field The specified field of table to be checked
     * @return boolean True if found extra property 'purify' in the table definition and is 'html', false otherwise
     */
    private function _fieldIsHtml(Doctrine_Table $table, $field)
    {
        $definition = $table->getDefinitionOf($field);

        if (@isset($definition['extra']['purify'])) {
            return $definition['extra']['purify'] === 'html';
        } else {
            return false;
        }
    }

    /**
     * A helper to derive a logical name from a physical field name in a particular table
     *
     * @param Doctrine_Table $table The specified doctrine table object to be checked
     * @param string $field The specified field of table to be checked
     * @return string|null The defined value of the extra property 'logicalName', null if is not set
     */
    private function _getLogicalNameForField(Doctrine_Table $table, $field)
    {
        $definition = $table->getDefinitionOf($field);

        if (isset($definition['extra']['logicalName'])) {
            return $definition['extra']['logicalName'];
        } else {
            return null;
        }
    }

    /**
     * Determine whether a particular field on a particular table should be a foreign key and an id type
     *
     * @param Doctrine_Table $table The specified doctrine table object to be checked
     * @param string $field The specified field of table to be checked
     * @return relation if found relation property 'local' and 'foreign' is id type, null otherwise
     */
    private function _fieldHasRelation(Doctrine_Table $table, $field)
    {
        $relations = $table->getRelations();

        foreach ($relations as $name => $relation) {
            if (strtolower($field) === strtolower($relation->getLocal())
                && 'id' === strtolower($relation->getForeign())) {

               return $relation;
            }
        }

        return null;
    }

    /**
     * A helper to derive a field name from a relation and a field id in a particular table
     *
     * @param Doctrine_Relation $relation The specified doctrine relation object to be checked
     * @param string $field The specified field of table to be checked
     * @return string|null The defined value if name field found, null otherwise
     */
    private function _getRelationFieldName(Doctrine_Relation $relation, $field)
    {

        $record = $relation->getTable()->findOneById($field);

        // For SecurityControl name, it needs to combine the names in SecurityControl table and
        // SecurityControlCatalog table to be an unique name.
        if ($record instanceof Fisma_Doctrine_Behavior_AuditLoggable_AuditLogProvider) {
            return $record->getAuditLogValue();
        } elseif ($record && $record->contains('name')) {
            return $record->name;
        } else {
            return $field;
        }

        return null;
    }
}
