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
 * Add incident workflow step table
 * 
 * This file contains generated code... skip standards check.
 * 
 * @package Migration
 * @copyright (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @author Mark E. Haase <mhaase@endeavorsystems.com> 
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Version47 extends Doctrine_Migration_Base
{
    public function up()
    {
		$this->createTable('ir_step', array(
             'id' => 
             array(
              'type' => 'integer',
              'length' => 8,
              'autoincrement' => true,
              'primary' => true,
             ),
             'cardinality' => 
             array(
              'type' => 'integer',
              'comment' => 'The order of this step relative to the other steps within this workflow',
              'length' => 8,
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
             'workflowid' => 
             array(
              'type' => 'integer',
              'comment' => 'The user who left this comment',
              'length' => 8,
             ),
             'roleid' => 
             array(
              'type' => 'integer',
              'comment' => 'The role required to complete this step',
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
		$this->dropTable('ir_step');
    }
}
