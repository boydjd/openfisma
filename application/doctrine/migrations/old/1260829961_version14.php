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
 * Create a new user audit log table
 *
 * @author     Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Migration
 */
class Version14 extends Doctrine_Migration_Base
{
    /**
     * Create table
     */
    public function up()
    {
        $this->createTable(
            'user_audit_log', 
            array(
                'id' => array(
                    'primary' => true,
                    'autoincrement' => true,
                    'type' => 'integer',
                    'length' => 8,
                ),
                'createdts' => array(
                    'comment' => 'The timestamp when this entry was created',
                    'type' => 'timestamp',
                    'length' => 25,
                ),
                'message' => array(
                    'comment' => 'The log message',
                    'type' => 'string',
                    'length' => NULL,
                ),
                'objectid' => array(
                    'comment' => 'The parent object which this log entry refers to',
                    'type' => 'integer',
                    'length' => 8,
                ),
                'userid' => array(
                    'comment' => 'The user who created this log entry',
                    'type' => 'integer',
                    'length' => 8,
                ),
            ), 
            array(
                'indexes' => array(),
                'primary' => array(0 => 'id'),
            )
        );

        $this->createForeignKey(
            'user_audit_log', 
            'user_audit_log_objectid_user_id_idx',
            array(
                'local' => 'objectid',
                'foreign' => 'id',
                'foreignTable' => 'user'
            )
        );
        
        $this->createForeignKey(
            'user_audit_log', 
            'user_audit_log_userid_user_id_idx',
            array(
                'local' => 'userid',
                'foreign' => 'id',
                'foreignTable' => 'user'
            )
        );
    }
    
    /**
     * Drop table
     */
    public function down()
    {
        $this->dropTable('user_audit_log');
    }
}
