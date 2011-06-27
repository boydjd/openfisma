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
 * Convert minutes to seconds
 *
 * @author     Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Migration
 */
class Version13 extends Doctrine_Migration_Base
{
    /**
     * Convert minutes to seconds
     * 
     * @return void
     */
    public function up()
    {
        $current = Doctrine::getTable('Configuration')->findOneByName('session_inactivity_period');
        
        $current->value *= 60;
        
        $current->save();
    }
    
    /**
     * Convert seconds to minutes
     * 
     * @return void
     */
    public function down()
    {
        $current = Doctrine::getTable('Configuration')->findOneByName('session_inactivity_period');
        
        $current->value = round($current->value / 60);
        
        $current->save();
    }
}
