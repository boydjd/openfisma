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
 * Add incident audit log table
 * 
 * This file contains generated code... skip standards check.
 * 
 * @package Migration
 * @copyright (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @author Mark E. Haase <mhaase@endeavorsystems.com> 
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Version41 extends Doctrine_Migration_Base
{
    public function up()
    {
		$this->createTable('incident_audit_log', array(
             'id' => 
             array(
              'primary' => true,
              'autoincrement' => true,
              'type' => 'integer',
              'length' => 8,
             ),
             'createdts' => 
             array(
              'comment' => 'The timestamp when this entry was created',
              'type' => 'timestamp',
              'length' => 25,
             ),
             'message' => 
             array(
              'comment' => 'The log message',
              'type' => 'string',
              'length' => NULL,
             ),
             'objectid' => 
             array(
              'comment' => 'The parent object which this log entry refers to',
              'type' => 'integer',
              'length' => 8,
             ),
             'userid' => 
             array(
              'comment' => 'The user who created this log entry',
              'type' => 'integer',
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
		$this->dropTable('incident_audit_log');
    }
}
