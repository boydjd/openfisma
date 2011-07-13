<?php
// @codingStandardsIgnoreFile
/**
 * Copyright (c) 2010 Endeavor Systems, Inc.
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
 * Add foreign keys for cookie table
 *
 * @package Migration
 * @copyright (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @author Mark E. Haase <mhaase@endeavorsystems.com>
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Version83 extends Doctrine_Migration_Base
{
    public function up()
    {
		$this->createForeignKey('cookie', 'cookie_userid_user_id', array(
             'name' => 'cookie_userid_user_id',
             'local' => 'userid',
             'foreign' => 'id',
             'foreignTable' => 'user',
             ));
 		$this->addIndex('cookie', 'userCookie', array(
              'fields' => 
              array(
               0 => 'name',
               1 => 'userid',
              ),
              'type' => 'unique',
              ));
		$this->addIndex('cookie', 'userid', array(
             'fields' => 
             array(
              0 => 'userid',
             ),
             ));
    }

    public function down()
    {
		$this->dropForeignKey('cookie', 'cookie_userid_user_id');
		$this->removeIndex('cookie', 'userCookie', array(
             'fields' => 
             array(
              0 => 'name',
              1 => 'userid',
             ),
             'type' => 'unique',
             ));
		$this->removeIndex('cookie', 'userid', array(
             'fields' => 
             array(
              0 => 'userid',
             ),
             ));
    }
}
