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
 * Add incident artifact table
 * 
 * This file contains generated code... skip standards check.
 * 
 * @package Migration
 * @copyright (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @author Mark E. Haase <mhaase@endeavorsystems.com> 
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Version40 extends Doctrine_Migration_Base
{
    public function up()
    {
		$this->createTable('incident_artifact', array(
             'id' => 
             array(
              'primary' => true,
              'autoincrement' => true,
              'type' => 'integer',
              'length' => 8,
             ),
             'createdts' => 
             array(
              'comment' => 'The timestamp when this artifact was uploaded',
              'type' => 'timestamp',
              'length' => 25,
             ),
             'filename' => 
             array(
              'comment' => 'The file name for this artifact',
              'type' => 'string',
              'length' => 255,
             ),
             'mimetype' => 
             array(
              'comment' => 'The MIME type for this artifact',
              'type' => 'string',
              'length' => 255,
             ),
             'filesize' => 
             array(
              'comment' => 'File size in bytes',
              'type' => 'string',
              'length' => NULL,
             ),
             'comment' => 
             array(
              'comment' => 'Comment associated with this artifact',
              'type' => 'string',
              'length' => NULL,
             ),
             'objectid' => 
             array(
              'comment' => 'The parent object to which this artifact belongs',
              'type' => 'integer',
              'length' => 8,
             ),
             'userid' => 
             array(
              'comment' => 'The user who uploaded this artifact',
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
		$this->dropTable('incident_artifact');
    }
}
