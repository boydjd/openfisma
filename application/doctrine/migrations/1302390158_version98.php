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
 * Add Storage model table.
 *
 * @package   Migration
 * @copyright (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @author    Andrew Reeves <andrew.reeves@endeavorsystems.com>
 * @license   http://www.openfisma.org/content/license GPLv3
 */
class Version98 extends Doctrine_Migration_Base
{
    /**
     * Add Storage model's table to the database.
     *
     * @return void
     */
    public function up()
    {
        $this->createTable(
            'storage',
            array(
                'id' => array('type' => 'integer', 'length' => '8', 'autoincrement' => '1', 'primary' => '1'),
                'userid' => array('type' => 'integer', 'length' => '8'),
                'namespace' => array('type' => 'string', 'length' => '255'),
                'data' => array( 'type' => 'object', 'length' => '')
            ),
            array('primary' => array(0 => 'id'))
        );
    }

    /**
     * Remove Storage model's table from the database.
     *
     * @return void
     */
    public function down()
    {
        $this->dropTable('storage');
    }
}
