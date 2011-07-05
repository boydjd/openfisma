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
 * Generic lockable behavior for records. Allows a record to be locked or unlocked.
 * Throws an exception if the record is modified while it is locked if the user does not have the proper privileges.
 * 
 * @package Fisma
 * @subpackage Fisma_Doctrine_Behavior_Lockable
 * @copyright (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @author Josh Boyd <joshua.boyd@endeavorsystems.com> 
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Fisma_Doctrine_Behavior_Lockable extends Doctrine_Template
{
    /**
     * Add isLocked column and add the listener
     * 
     * @return void
     */
    public function setTableDefinition()
    {
        $this->hasColumn(
            'isLocked', 'boolean', 25, array(
                'type' => 'boolean',
                'length' => '25',
                'notnull' => true,
                'default' => 0
            )
        );

        $this->addListener(new Fisma_Doctrine_Behavior_Lockable_Listener());
    }
}
