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
 * Rename the current security control table, then create new security control table, security catalog control table,
 * and security control enhancements table.
 * 
 * @package Migration
 * @copyright (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @author Mark E. Haase <mhaase@endeavorsystems.com>
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Version60 extends Doctrine_Migration_Base
{
    /**
     * Want to rename current security_control table. This will be used to update pointers in finding table
     * to point at the new rows in the new security_control table. Foreign key needs to be dropped.
     */
    public function up()
    {
        $this->dropForeignKey('finding', 'finding_securitycontrolid_security_control_id');

        $this->renameTable('security_control', 'security_control_backup');
        
		$this->createTable('security_control', array(
             'id' => 
             array(
              'type' => 'integer',
              'length' => 8,
              'autoincrement' => true,
              'primary' => true,
             ),
             'code' => 
             array(
              'type' => 'string',
              'fixed' => 1,
              'comment' => 'The control number, e.g. AC-05',
              'extra' => 
              array(
              'searchIndex' => 'unstored',
              ),
              'length' => 5,
             ),
             'name' => 
             array(
              'type' => 'string',
              'comment' => 'Name of the control',
              'extra' => 
              array(
              'searchIndex' => 'unstored',
              ),
              'length' => 255,
             ),
             'class' => 
             array(
              'type' => 'enum',
              'values' => 
              array(
              0 => 'MANAGEMENT',
              1 => 'OPERATIONAL',
              2 => 'TECHNICAL',
              ),
              'extra' => 
              array(
              'searchIndex' => 'keyword',
              ),
              'length' => NULL,
             ),
             'subclass' => 
             array(
              'type' => 'string',
              'extra' => 
              array(
              'searchIndex' => 'unstored',
              ),
              'length' => 255,
             ),
             'family' => 
             array(
              'type' => 'string',
              'extra' => 
              array(
              'searchIndex' => 'unstored',
              ),
              'length' => 255,
             ),
             'control' => 
             array(
              'type' => 'string',
              'extra' => 
              array(
              'searchIndex' => 'unstored',
              'purify' => 'html',
              ),
              'length' => NULL,
             ),
             'supplementalguidance' => 
             array(
              'type' => 'string',
              'extra' => 
              array(
              'searchIndex' => 'unstored',
              'purify' => 'html',
              ),
              'length' => NULL,
             ),
             'externalreferences' => 
             array(
              'type' => 'string',
              'extra' => 
              array(
              'searchIndex' => 'unstored',
              'purify' => 'html',
              ),
              'length' => NULL,
             ),
             'prioritycode' => 
             array(
              'type' => 'enum',
              'values' => 
              array(
              0 => 'P0',
              1 => 'P1',
              2 => 'P2',
              3 => 'P3',
              ),
              'extra' => 
              array(
              'searchIndex' => 'keyword',
              ),
              'length' => NULL,
             ),
             'controllevel' => 
             array(
              'type' => 'enum',
              'values' => 
              array(
              0 => 'NONE',
              1 => 'LOW',
              2 => 'MODERATE',
              3 => 'HIGH',
              ),
              'extra' => 
              array(
              'searchIndex' => 'keyword',
              'searchAlias' => 'level',
              ),
              'length' => NULL,
             ),
             'securitycontrolcatalogid' => 
             array(
              'type' => 'integer',
              'comment' => 'Foreign key to the catalog which this control belongs to',
              'length' => 8,
             ),
             ), array(
             'indexes' => 
             array(
             ),
             'primary' => 
             array(
              0 => 'id',
             ),
             ));
             
 		$this->createTable('security_control_catalog', array(
              'id' => 
              array(
               'type' => 'integer',
               'length' => 8,
               'autoincrement' => true,
               'primary' => true,
              ),
              'name' => 
              array(
               'type' => 'string',
               'fixed' => 1,
               'extra' => 
               array(
               'searchIndex' => 'unstored',
               ),
               'length' => NULL,
              ),
              ), array(
              'indexes' => 
              array(
              ),
              'primary' => 
              array(
               0 => 'id',
              ),
              ));

		$this->createTable('security_control_enhancement', array(
             'id' => 
             array(
              'type' => 'integer',
              'length' => 8,
              'autoincrement' => true,
              'primary' => true,
             ),
             'level' => 
             array(
              'type' => 'enum',
              'values' => 
              array(
              0 => 'LOW',
              1 => 'MODERATE',
              2 => 'HIGH',
              ),
              'comment' => 'Indicates the lowest baseline that includes this enhancement.',
              'length' => NULL,
             ),
             'description' => 
             array(
              'type' => 'string',
              'extra' => 
              array(
              'purify' => 'html',
              ),
              'length' => NULL,
             ),
             'securitycontrolid' => 
             array(
              'type' => 'integer',
              'comment' => 'Foreign key',
              'length' => 8,
             ),
             ), array(
             'indexes' => 
             array(
             ),
             'primary' => 
             array(
              0 => 'id',
             ),
             ));
             
 		$this->createForeignKey('security_control', 'sssi', array(
              'name' => 'sssi',
              'local' => 'securitycontrolcatalogid',
              'foreign' => 'id',
              'foreignTable' => 'security_control_catalog',
              ));
              
 		$this->createForeignKey('security_control_enhancement', 'sssi_1', array(
              'name' => 'sssi_1',
              'local' => 'securitycontrolid',
              'foreign' => 'id',
              'foreignTable' => 'security_control',
              ));
    }

    /**
     * Drop the tables created above and rename the backup table to its original name
     */
    public function down()
    {
        $this->dropForeignKey('security_control_enhancement', 'sssi_1');

        $this->dropForeignKey('security_control', 'sssi');
        
		$this->dropTable('security_control');
		$this->dropTable('security_control_catalog');
		$this->dropTable('security_control_enhancement');
		
        $this->renameTable('security_control_backup', 'security_control');
        
		$this->createForeignKey('finding', 'finding_securitycontrolid_security_control_id', array(
             'name' => 'finding_securitycontrolid_security_control_id',
             'local' => 'securitycontrolid',
             'foreign' => 'id',
             'foreignTable' => 'security_control',
             ));
    }
}
