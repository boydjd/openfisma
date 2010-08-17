<?php
/**
 * Copyright (c) 2010 Endeavor Systems, Inc.
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
 * Implementation of an ezcSearchDefinitionManager that uses Doctrine schemas and other model metadata to construct
 * search definitions on-the-fly.
 *
 * @author     Mark E. Haase
 * @copyright  (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Search
 */
class Fisma_Search_DefinitionManager implements ezcSearchDefinitionManager
{
    /**
     * Returns the search definition for a model named $type
     * 
     * Implements the required interface ezcSearchDefinitionManager
     *
     * @param string $type Name of the model to get the search definition for
     * @return ezcSearchDocumentDefinition
     */
    public function fetchDefinition($type)
    {
        $table = Doctrine::getTable($type);

        if (!$table) {
            throw new ezcSearchDefinitionNotFoundException("No table exists for type: $type");
        }

        $searchDefinition = new ezcSearchDocumentDefinition($type);
        $searchDefinition->idProperty = 'id';

        // Add the 'databaseId' field which corresponds to the objects primary key
        $pkField = new ezcSearchDefinitionDocumentField('primaryKey', ezcSearchDocumentDefinition::INT);
        $searchDefinition->fields['primaryKey'] = $pkField;

        // Now add any other eligible columns
        foreach ($table->getColumns() as $columnName => $columnDefinition) {

            $fieldName = $table->getFieldName($columnName);

            if ($this->_isIndexable($columnDefinition)) {
                $documentField = $this->_getFieldForColumn($fieldName, $columnDefinition);

                $searchDefinition->fields[$fieldName] = $documentField;
            }
        }

        return $searchDefinition;
    }

    /**
     * Generate a search engine field definition for a particular column
     *
     * @param string $fieldName
     * @param string $columnDefinition Doctrine's definition for this column
     * @return ezcSearchDefinitionDocumentField|null
     */
    private function _getFieldForColumn($fieldName, $columnDefinition)
    {
        $defaultSearchOptions = array(
            'boost' => 1.0,
            'highlight' => true
        );        
        
        // Search options can have the literal value "true" or could be an array of search options
        if (true === $columnDefinition['extra']['search']) {
            $searchOptions = $defaultSearchOptions;
        } else {
            $searchOptions = array_merge($defaultSearchOptions, $columnDefinition['extra']['search']);
        }

        // Determine what type of Solr data type to use based on this column's metadata
        if (isset($columnDefinition['extra']['purify']) && 'html' == $columnDefinition['extra']['purify']) {
            $searchType = ezcSearchDocumentDefinition::HTML;
        } elseif (isset($columnDefinition['extra']['search']['tokenize']) &&
                  !$columnDefinition['extra']['search']['tokenize']) {
            $searchType = ezcSearchDocumentDefinition::STRING;
        } else {
            $searchType = ezcSearchDocumentDefinition::TEXT;
        }

        // Create field and add it to the document definition
        $documentField = new ezcSearchDefinitionDocumentField(
            $fieldName,
            $searchType,
            $searchOptions['boost'],
            null,
            null,
            $searchOptions['highlight']
        );

        return $documentField;
    }

    /**
     * Returns whether this column meets the OpenFISMA criteria for indexing
     *
     * Currently, the following criteria are required.
     * a. Type of column is "string"
     * b. The 'extra' metadata (in the YAML schema definition) includes a 'search' field
     *
     * @param array $columnDefinition
     * @return bool
     */
    public function _isIndexable($columnDefinition)
    {
        return ('string' == $columnDefinition['type'] && isset($columnDefinition['extra']['search']));
    }
}