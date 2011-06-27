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
 * Drop the system* privileges (which are now covered by the organization* privileges) and drop the orgSpecific flag
 * 
 * There is no logical distinction between organization & system level access control, so I'm removing the physical
 * distinction also.
 * 
 * The orgSpecific flag is deprecated because this information is communicated in the class definition by implementing
 * the Fisma_Zend_Acl_OrganizationDependency interface.
 * 
 * @package Migration
 * @copyright (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @author Mark E. Haase <mhaase@endeavorsystems.com>
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Version21 extends Doctrine_Migration_Base
{
    /**
     * Drop the system* privileges and orgSpecific column
     */
    public function up()
    {
        // Delete privileges inside a transaction
        Doctrine_Manager::connection()->beginTransaction();
        
        $systemPrivileges = Doctrine::getTable('Privilege')->findByResource('system');
        
        foreach ($systemPrivileges as $privilege) {
            $privilege->unlink('Role');
            $privilege->save();

            $privilege->delete();
        }
        
        Doctrine_Manager::connection()->commit();
        
        // Now drop column. This is outside the transaction since the DDL will trigger an autocommit on most DBMS
        $this->removeColumn('privilege', 'orgspecific');
    }

    /**
     * This migration cannot be reversed because some data is irreversably destroyed in the up() process
     */
    public function down()
    {
        throw new Doctrine_Migration_IrreversibleMigrationException();
    }
}
