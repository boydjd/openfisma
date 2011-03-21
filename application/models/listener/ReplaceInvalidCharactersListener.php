<?php
/**
 * Copyright (c) 2011 Endeavor Systems, Inc.
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
 * ReplaceInvalidCharactersListener 
 * 
 * @uses Fisma_Doctrine_Record_Listener
 * @package Listener
 * @copyright (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @author Josh Boyd <joshua.boyd@endeavorsystems.com> 
 * @license http://www.openfisma.org/content/license GPLv3
 */
class ReplaceInvalidCharactersListener extends Fisma_Doctrine_Record_Listener
{
    /**
     * Replace invalid characters with good ones 
     * 
     * @param Doctrine_Event $event 
     * @access public
     * @return void
     */
    public function preSave(Doctrine_Event $event) 
    {
        if (!self::$_listenerEnabled) {
            return;
        }

        $invoker = $event->getInvoker();
        $modified = $invoker->getModified();
        $table = $invoker->getTable();
        
        foreach ($modified as $field => $value) {
            $fieldDefinition = $table->getDefinitionOf($field);

            // If the field is set to doNotModify, then do not modify it 
            if (
                isset($fieldDefinition['extra']['doNotModify']) &&
                $fieldDefinition['extra']['doNotModify']
            ) {
                continue;
            }

            if ($fieldDefinition['type'] == 'string') {
               $invoker[$field] = Fisma_String::replaceInvalidChars($value); 
            }
        }
    }
}
