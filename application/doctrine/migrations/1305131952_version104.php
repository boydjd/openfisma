<?php
/**
 * Copyright (c) 2011 Endeavor Systems, Inc.
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
 * Remove cookie model.
 * 
 * @package Migration
 * @copyright (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @author Andrew Reeves <andrew.reeves@endeavorsystems.com>
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Version104 extends Doctrine_Migration_Base
{
    public function up()
    {
        $this->dropTable('cookie');
    }

    public function down()
    {
        $this->createTable(
            'cookie',
            array(
                'id' => array('type' => 'integer', 'length' => '8', 'autoincrement' => '1', 'primary' => '1'),
                'name' => array('type' => 'string', 'comment' => 'The name of this cookie', 'length' => '255'),
                'value' => array('type' => 'string', 'comment' => 'The value of this cookie', 'length' => '255'),
                'userid' => array('type' => 'integer', 'comment' => 'Foreign key to user table', 'length' => '8')
            ),
            array(
                'type' => '',
                'indexes' => array(
                    'userCookie' => array('fields' => array( 0 => 'name', 1 => 'userid',), 'type' => 'unique')
                ),
                'primary' => array(0 => 'id'),
                'collate' => '',
                'charset' => ''
            )
        );
    }
}
