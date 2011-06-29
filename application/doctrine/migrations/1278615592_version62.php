<?php
// @codingStandardsIgnoreFile
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
 * Load metadata for security controls and catalogs from YAML fixture files
 * 
 * @package Migration
 * @copyright (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @author Mark E. Haase <mhaase@endeavorsystems.com>
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Version62 extends Doctrine_Migration_Base
{
    /**
     * Use the security_control_backup table to reassign foreign key references between finding and security_control.
     * 
     * This is ugly to do in Doctrine, so I'm going to use a raw MySQL DBH.
     * 
     * Drop the backup table when done and create a new FK from finding to security_control
     */
    public function up()
    {
        $db = Doctrine_Manager::getInstance()->getConnection(0)->getDbh();
        
        // Create a temp table that contains join conditions for mapping old security control to new security control
        $db->exec('create temporary table security_control_map (old int, new int)');
        
        $db->exec('
            insert into security_control_map 
                select old.id, new.id
                from security_control_backup old
                inner join security_control new on old.code = new.code and new.securitycontrolcatalogid=3
        ');
        
        // Use map table to convert old foreign keys to new foreign keys
        $db->exec('
            update finding f set securitycontrolid = (
            	select m.new 
            	from security_control_map m
            	where f.securitycontrolid = m.old
            )
        ');
        
        $this->dropTable('security_control_backup');
        
		$this->createForeignKey('finding', 'finding_securitycontrolid_security_control_id', array(
             'name' => 'finding_securitycontrolid_security_control_id',
             'local' => 'securitycontrolid',
             'foreign' => 'id',
             'foreignTable' => 'security_control',
             ));
    }
    
    /**
     * This is not reversible since we are throwing away foreign key information in the up() method
     */
    public function down()
    {
        throw new Doctrine_Migration_IrreversibleMigrationException('Version 60 is not reversible.');
    }
}
