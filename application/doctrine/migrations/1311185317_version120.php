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
 * Version120
 * 
 * @uses Doctrine_Migration_Base
 * @package Migration 
 * @copyright (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @author Andrew Reeves <andrew.reeves@endeavorsystems.com>
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Version120 extends Doctrine_Migration_Base
{
    public function up()
    {
        $this->renameTable('user', 'poc');
        $this->createTable(
            'poc_audit_log',
            array(
                'id' => array('type' => 'integer', 'length' => '8', 'autoincrement' => '1', 'primary' => '1'),
                'createdts' => array('type' => 'timestamp', 'comment' => 'The timestamp when this entry was created'),
                'message' => array('type' => 'string', 'comment' => 'The log message'),
                'objectid' => array(
                    'type' => 'integer',
                    'length' => 8,
                    'comment' => 'The parent object which this log entry refers to'
                ),
                'userid' => array(
                    'type' => 'integer',
                    'length' => 8,
                    'comment' => 'The user who created this log entry'
                )
            ),
            array(
                'type' => '',
                'indexes' => array(),
                'primary' => array(0 => 'id'),
                'collate' => 'utf8_unicode_ci',
                'charset' => 'utf8'
            )
        ); 
        $this->createTable(
            'poc_comment',
            array(
                'id' => array('type' => 'integer', 'length' => '8', 'autoincrement' => '1', 'primary' => '1'),
                'createdts' => array('type' => 'timestamp', 'comment' => 'The timestamp when this entry was created'),
                'comment' => array('type' => 'string', 'comment' => 'The text of the comment'),
                'objectid' => array(
                    'type' => 'integer',
                    'length' => 8,
                    'comment' => 'The parent object to which this comment belongs',
                ),
                'userid' => array(
                    'type' => 'integer',
                    'length' => 8,
                    'comment' => 'The user who created comment',
                )
            ),
            array(
                'type' => '',
                'indexes' => array(),
                'primary' => array(0 => 'id'),
                'collate' => 'utf8_unicode_ci',
                'charset' => 'utf8'
            )
        );

        $this->renameColumn('finding', 'assignedtouserid', 'pocid');

        $this->addColumn('system', 'aggregatesystemid', 'integer', 8);
        $this->addColumn('poc', 'type', 'string', 255);
        $this->addColumn(
            'poc',
            'reportingorganizationid',
            'integer',
            8,
            array('comment' => "Foreign key to the point of contact''s reporting organization.")
        );
    }

    public function postUp()
    {
        $conn = Doctrine_Manager::connection();
        $selectSql = "SELECT id FROM organization WHERE level = 0 LIMIT 1";
        $org = $conn->fetchRow($selectSql);
        $orgId = $org['id'];
        $updateSql = "UPDATE poc SET reportingorganizationid = ? WHERE reportingorganizationid IS NULL";
        $conn->exec($updateSql, array($orgId));
    }

    public function down()
    {
    }
}
