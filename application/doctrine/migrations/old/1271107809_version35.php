<?php
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
 * Support new module functionality
 * 
 * @package Migration
 * @copyright (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @author Mark E. Haase <mhaase@endeavorsystems.com>
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Version35 extends Doctrine_Migration_Base
{
    /**
     * Add module table
     */
    public function up()
    {
        // Create module table
        $this->createTable(
            'module', 
            array(
                'id' => array(
                    'type' => 'integer',
                    'length' => '8',
                    'autoincrement' => '1',
                    'primary' => '1',
                ),
                'name' => array(
                    'type' => 'string',
                    'length' => '255',
                ),
                'enabled' => array(
                    'type' => 'boolean',
                ),
                'canBeDisabled' => array(
                    'type' => 'boolean',
                )
            )
        );
    }

    /**
     * Remove module table
     */
    public function down()
    {
        $this->dropTable('module');
    }
}
