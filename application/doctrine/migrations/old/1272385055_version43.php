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
 * Add incident category table
 * 
 * This file contains generated code... skip standards check.
 * 
 * @package Migration
 * @copyright (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @author Mark E. Haase <mhaase@endeavorsystems.com> 
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Version43 extends Doctrine_Migration_Base
{
    public function up()
    {
		$this->createTable('ir_category', array(
             'id' => 
             array(
              'type' => 'integer',
              'length' => 8,
              'autoincrement' => true,
              'primary' => true,
             ),
             'createdts' => 
             array(
              'notnull' => true,
              'type' => 'timestamp',
              'length' => 25,
             ),
             'modifiedts' => 
             array(
              'notnull' => true,
              'type' => 'timestamp',
              'length' => 25,
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
             'category' => 
             array(
              'type' => 'enum',
              'values' => 
              array(
              0 => 'CAT0',
              1 => 'CAT1',
              2 => 'CAT2',
              3 => 'CAT3',
              4 => 'CAT4',
              5 => 'CAT5',
              6 => 'CAT6',
              ),
              'comment' => 'Maps this category to a pre-defined US-CERT category',
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
    }

    public function down()
    {
		$this->dropTable('ir_category');
    }
}
