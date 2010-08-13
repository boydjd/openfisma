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
 * Builds keyword search indexes
 * 
 * @author     Mark E. Haase
 * @copyright  (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Search
 */
class Fisma_Search_Index_Builder
{
    /**
     * The number of records to fetch at a time when creating a new index
     * 
     * @var int
     */
    const FETCH_ROWS = 100;    

    /**
     * Builds an index for the specified class
     * 
     * This deletes any pre-existing documents in the index then indexes all of the data in this class's table
     * 
     * @param string $className
     */
    public function buildIndexForClass($className)
    {
        $table = Doctrine::getTable($className);

        $session = $table->getSearchSession();
        
        // Delete all existing documents first
        $deleteQuery = $session->createDeleteQuery($className);
        
        $session->delete($deleteQuery);
        
        // Get a total count of all records
        $allRecordsQuery = Doctrine_Query::create()
                           ->from($className);

        $totalRecords = $allRecordsQuery->count();
        
        $progressBar = new Zend_ProgressBar(new Zend_ProgressBar_Adapter_Console, 0, $totalRecords);
        
        // Create new documents and index them
        $currentRecord = 0;
        while ($currentRecord < $totalRecords) {
            
            // Get the next set of records and index them
            $allRecordsQuery->limit(self::FETCH_ROWS)
                            ->offset($currentRecord);

            $recordSet = $allRecordsQuery->execute();
            
            foreach ($recordSet as $record) {
                $session->index($record);
                
                $currentRecord++;
            }

            $recordSet->free();
            
            $progressBar->update($currentRecord);
        }
        
    }
}
