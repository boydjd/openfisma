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
 * Add incident workflow table
 * 
 * This file contains generated code... skip standards check.
 * 
 * @package Migration
 * @copyright (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @author Mark E. Haase <mhaase@endeavorsystems.com> 
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Version46 extends Doctrine_Migration_Base
{
    public function up()
    {
		$this->createTable('ir_incident_workflow', array(
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
              'extra' => 
              array(
              'purify' => 'plaintext',
              ),
              'length' => 255,
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
             'cardinality' => 
             array(
              'type' => 'integer',
              'length' => 8,
             ),
             'status' => 
             array(
              'type' => 'enum',
              'values' => 
              array(
              0 => 'queued',
              1 => 'current',
              2 => 'completed',
              ),
              'length' => NULL,
             ),
             'completets' => 
             array(
              'type' => 'timestamp',
              'length' => 25,
             ),
             'comments' => 
             array(
              'type' => 'string',
              'extra' => 
              array(
              'purify' => 'html',
              ),
              'length' => NULL,
             ),
             'incidentid' => 
             array(
              'type' => 'integer',
              'comment' => 'Foreign key to the incident to which this workflow step belongs',
              'length' => 8,
             ),
             'roleid' => 
             array(
              'type' => 'integer',
              'comment' => 'Foreign key to the role which is required to execute this workflow step',
              'length' => 8,
             ),
             'userid' => 
             array(
              'type' => 'integer',
              'comment' => 'Foreign key to the user who completed this step',
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
    }

    public function down()
    {
		$this->dropTable('ir_incident_workflow');
    }
}
