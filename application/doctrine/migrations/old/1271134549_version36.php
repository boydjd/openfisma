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
 * Add metadata for modules
 * 
 * 
 * @package Migration
 * @copyright (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @author Mark E. Haase <mhaase@endeavorsystems.com>
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Version36 extends Doctrine_Migration_Base
{
    /**
     * Add module metadata
     */
    public function up()
    {
        // Create module metadata
        $findingModule = new Module();
        $findingModule->name = 'Findings';
        $findingModule->canBeDisabled = false;
        $findingModule->enabled = true;
        $findingModule->save();

        /* The IR module is enabled by default in our fixtures but disabled by default in the migration, because we
         * don't want to automatically enable new functionality on an existing installation.
         */
        $incidentModule = new Module();
        $incidentModule->name = 'Incident Reporting';
        $incidentModule->canBeDisabled = true;
        $incidentModule->enabled = false; 
        $incidentModule->save();

        $systemInventoryModule = new Module();
        $systemInventoryModule->name = 'System Inventory';
        $systemInventoryModule->canBeDisabled = false;
        $systemInventoryModule->enabled = true;
        $systemInventoryModule->save();
    }
    
    /**
     * Remove module metadata
     */
    public function down()
    {
        Doctrine::getTable('Module')->findOneByName('Findings')->delete();
        Doctrine::getTable('Module')->findOneByName('Incident Reporting')->delete();
        Doctrine::getTable('Module')->findOneByName('System Inventory')->delete();
    }
}