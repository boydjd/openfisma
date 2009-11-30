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
 * <http://www.gnu.org/licenses/>.
 */

/**
 * This listener updates the Lucene index whenever a record is updated.
 * 
 * @author     Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2009 (http://www.endeavorsystems.com)
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Listener
 * @version    $Id$
 */
class IndexListener extends Doctrine_Record_Listener
{
    /**
     * New records always get indexed
     * 
     * @param Doctrine_Event $event
     */
    public function postInsert(Doctrine_Event $event)
    {
        $record = $event->getInvoker();

        $index = new Fisma_Index(get_class($record));
        $index->update($record);
    }

    /**
     * Updated records are only indexed if one of its indexable fields was modified
     * 
     * @param Doctrine_Event $event
     */
    public function postUpdate(Doctrine_Event $event)
    {
        $record = $event->getInvoker();
        $modified = $record->getLastModified();

        // A quick shortcut:
        if (0 == count($modified)) {
            return;
        }

        // Determine whether any of the indexable fields have changed
        $needsIndex = false;
        $table = $record->getTable();
        foreach ($modified as $modifiedField => $modifiedValue) {
            $columnDef = $table->getColumnDefinition($modifiedField);
            if (isset($columnDef['extra']['searchIndex'])) {
                $needsIndex = true;
                break;
            }
        }

        // If an indexed field changed, then update the index
        if ($needsIndex) {
            $index = new Fisma_Index(get_class($record));
            $index->update($record);
        }
    }
    
    /**
     * Remove deleted records from the keyword index
     * 
     * @param Doctrine_Event $event
     */
    public function postDelete(Doctrine_Event $event)
    {
        $record = $event->getInvoker();

        $index = new Fisma_Index(get_class($record));
        $index->delete($record);
    }
}
