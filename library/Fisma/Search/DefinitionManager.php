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
        
        $defaultSearchOptions = array(
            'boost' => 1.0,
            'highlight' => true
        );
        
        foreach ($table->getColumns() as $columnName => $columnDefinition) {

            // Merge the column's explicit search options into the default options
            $searchOptions = $defaultSearchOptions;

            if (isset($columnDefinition['search'])) {
                $searchOptions = array_merge($searchOptions, $columnDefinition['search']);
            }

            $type = $this->getSearchTypeFromColumnDefinition($columnDefinition);
            
            // Create field and add it to the document definition
            $documentField = new ezcSearchDefinitionDocumentField(
                $columnName, 
                $type, 
                $searchOptions['boost'], 
                null, 
                null, 
                $searchOptions['highlight']
            );

            $searchDefinition->fields[$columnName] = $documentField;
        }

        return $searchDefinition;
    }

    /**
     * Convert a Doctrine data type to an ezcSearchDocumentDefinition type
     * 
     * @param array $columnDefinition Doctrine's definition for this column
     * @return int One of the type constants in ezcSearchDocumentDefinition
     */
    public function getSearchTypeFromColumnDefinition($columnDefinition)
    {
        $searchType = null;

        switch ($columnDefinition['type']) {

            // Doctrine's string type maps to three different types in ezc search
            case 'string':
                if (isset($columnDefinition['extra']['purify']) && 'html' == $columnDefinition['extra']['purify']) {
                    $searchType = ezcSearchDocumentDefinition::HTML;
                } elseif (isset($columnDefinition['extra']['search']['tokenize']) &&
                          !$columnDefinition['extra']['search']['tokenize']) {
                    $searchType = ezcSearchDocumentDefinition::STRING;
                } else {
                    $searchType = ezcSearchDocumentDefinition::TEXT;
                }
                break;

            case 'integer':
                $searchType = ezcSearchDocumentDefinition::INT;
                break;

            default:
                throw new Fisma_Zend_Exception("No ezc search data type matches this doctrine data type: $type");
        }

        return $searchType;
    }
}
