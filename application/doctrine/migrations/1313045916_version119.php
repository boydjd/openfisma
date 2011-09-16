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
 * Convert seconds to minutes
 *
 * @package   Migration
 * @copyright (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @author    Ben Zheng <ben.zheng@reyosoft.com>
 * @license   http://www.openfisma.org/content/license GPLv3
 */
class Version119 extends Doctrine_Migration_Base
{
    /** 
     * Convert seconds to minutes
     * 
     * @return void 
     */
    public function up()
    {
        $conn = Doctrine_Manager::connection();

        $columns = array('session_inactivity_period', 'session_inactivity_notice');

        $options = array(
            'notblank' => '1',
            'unsigned' => '1',
            'comment' => 'Session timeout (minutes)',
            'default' => 0,
        );
        
        foreach ($columns as $column) {

            // Change column comment to 'Session timeout (minutes)'
            $this->changeColumn('configuration', $column, '2', 'integer', $options);

            // Convert seconds to minutes
            $updateSql = "UPDATE configuration SET $column = $column / 60";
            $conn->exec($updateSql);
        }
    }

    /** 
     * Convert minutes to seconds
     * 
     * @return void 
     */
    public function down()
    {
        $conn = Doctrine_Manager::connection();

        $columns = array('session_inactivity_period', 'session_inactivity_notice');

        $options = array(
            'notblank' => '1',
            'unsigned' => '1',
            'comment' => 'Session timeout (seconds)',
            'default' => 0,
        );
        
        foreach ($columns as $column) {
            $this->changeColumn('configuration', $column, '2', 'integer', $options);

            $updateSql = "UPDATE configuration SET $column = $column * 60";
            $conn->exec($updateSql);
        }
    }
}
