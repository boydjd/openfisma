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
 * Create tables vulnerability, vulnerability_bugtraq, vulnerability_cve, vulnerability_resolution, vulnerability_upload, 
 * vulnerability_xref, vulnerability_audit_log, vulnerability_comment.
 *
 * @package Migration
 * @copyright (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @author Christian Smith <christian.smith@endeavorsystems.com>
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Version86 extends Doctrine_Migration_Base
{
    public function up()
    {
        $this->dropForeignKey('finding', 'finding_assetid_asset_id');
        $this->dropForeignKey('finding_bugtraq', 'finding_bugtraq_bugtraq_id_bugtraq_id');
        $this->dropForeignKey('finding_bugtraq', 'finding_bugtraq_finding_id_finding_id');
        $this->dropForeignKey('finding_cve', 'finding_cve_cve_id_cve_id');
        $this->dropForeignKey('finding_cve', 'finding_cve_finding_id_finding_id');
        $this->dropForeignKey('finding_xref', 'finding_xref_finding_id_finding_id');

        $this->createTable('vulnerability', array(
            'id' => 
            array(
                'type' => 'integer',
                'length' => '8',
                'autoincrement' => '1',
                'primary' => '1',
            ),
            'createdts' => 
            array(
                'notnull' => '1',
                'type' => 'timestamp',
                'length' => '25',
            ),
            'discovereddate' => 
            array(
                'type' => 'date',
                'comment' => 'The date when the finding was discovered. This is self-reported by scanners.',
                'extra' => 
                array(
                    'auditLog' => '1',
                    'logicalName' => 'Date Discovered',
                ),
                'length' => '25',
            ),
            'closedts' => 
            array(
                'type' => 'timestamp',
                'comment' => 'The timestamp when this finding was closed',
                'extra' => 
                array(
                    'logicalName' => 'Closed Date',
                ),
                'length' => '25',
            ),
            'status' => 
            array(
                'type' => 'enum',
                'values' => 
                array(
                    0 => 'OPEN',
                    1 => 'FIXED',
                    2 => 'WONTFIX',
                ),
                'default' => 'OPEN',
                'extra' => 
                array(
                    'auditLog' => '1',
                    'logicalName' => 'Vulnerability Status',
                    'searchIndex' => 'unstored',
                ),
                'comment' => 'The current status.',
                'length' => '',
            ),
            'description' => 
            array(
                'type' => 'string',
                'extra' => 
                array(
                    'purify' => 'html',
                    'auditLog' => '1',
                    'logicalName' => 'Vulnerability Description',
                    'searchIndex' => 'unstored',
                ),
                'length' => '',
            ),
            'recommendation' => 
            array(
                'type' => 'string',
                'extra' => 
                array(
                    'purify' => 'html',
                    'auditLog' => '1',
                    'logicalName' => 'Recommendation',
                    'searchIndex' => 'unstored',
                ),
                'length' => '',
            ),
            'threat' => 
            array(
                'type' => 'string',
                'extra' => 
                array(
                    'purify' => 'html',
                    'auditLog' => '1',
                    'logicalName' => 'Description of Threat Source',
                    'searchIndex' => 'unstored',
                ),
                'comment' => 'Description of the threat source which affects this vulnerability',
                'length' => '',
            ),
            'threatlevel' => 
            array(
                'type' => 'enum',
                'values' => 
                array(
                    0 => 'LOW',
                    1 => 'MODERATE',
                    2 => 'HIGH',
                ),
                'extra' => 
                array(
                    'auditLog' => '1',
                    'logicalName' => 'Threat Level',
                    'searchIndex' => 'unstored',
                ),
                'comment' => 'A subjective assessment of the probability and impact of exploiting this vulnerability',
                'length' => '',
            ),
            'cvssbasescore' => 
            array(
                'type' => 'float',
                'extra' => 
                array(
                    'auditLog' => '1',
                    'logicalName' => 'CVSS Base Score',
                    'searchIndex' => 'unstored',
                ),
                'comment' => 'The CVSS Base Score of the vulnerability',
                'length' => '',
            ),
            'cvssvector' => 
            array(
                'type' => 'string',
                'comment' => 'The CVSS Vector of the vulnerability',
                'extra' => 
                array(
                    'auditLog' => '1',
                    'logicalName' => 'CVSS Vector',
                    'searchIndex' => 'unstored',
                ),
                'length' => '255',
            ),
            'assetid' => 
            array(
                'type' => 'integer',
                'comment' => 'Foreign key to the asset which this vulnerability is against',
                'length' => '8',
            ),
            'createdbyuserid' => 
            array(
                'type' => 'integer',
                'comment' => 'Foreign key to the user who created this vulnerability',
                'length' => '8',
            ),
            'resolutionid' => 
            array(
                'type' => 'integer',
                'comment' => 'Foreign key to the user-defined resolution for the vulnerability',
                'length' => '8',
            ),
            'modifiedts' => 
            array(
                'notnull' => '1',
                'type' => 'timestamp',
                'length' => '25',
            ),
            'deleted_at' => 
            array(
                'default' => '',
                'notnull' => '',
                'type' => 'timestamp',
                'length' => '25',
            ),
        ), array(
            'indexes' => 
            array(
                'descriptionindex' => 
                array(
                    'fields' => 
                    array(
                        'description' => 
                        array(
                            'length' => '20',
                        ),
                    ),
                ),
            ),
            'primary' => 
            array(
                0 => 'id',
            ),
        ));
        $this->createTable('vulnerability_bugtraq', array(
            'vulnerability_id' => 
            array(
                'type' => 'integer',
                'primary' => '1',
                'length' => '8',
            ),
            'bugtraq_id' => 
            array(
                'type' => 'integer',
                'primary' => '1',
                'length' => '8',
            ),
        ), array(
            'primary' => 
            array(
                0 => 'vulnerability_id',
                1 => 'bugtraq_id',
            ),
        ));
        $this->createTable('vulnerability_cve', array(
            'vulnerability_id' => 
            array(
                'type' => 'integer',
                'primary' => '1',
                'length' => '8',
            ),
            'cve_id' => 
            array(
                'type' => 'integer',
                'primary' => '1',
                'length' => '8',
            ),
        ), array(
            'primary' => 
            array(
                0 => 'vulnerability_id',
                1 => 'cve_id',
            ),
        ));
        $this->createTable('vulnerability_resolution', array(
            'id' => 
            array(
                'type' => 'integer',
                'length' => '8',
                'autoincrement' => '1',
                'primary' => '1',
            ),
            'name' => 
            array(
                'type' => 'string',
                'notblank' => '1',
                'length' => '',
            ),
            'description' => 
            array(
                'type' => 'string',
                'length' => '',
            ),
        ), array(
            'primary' => 
            array(
                0 => 'id',
            ),
        ));
        $this->createTable('vulnerability_upload', array(
            'vulnerabilityid' => 
            array(
                'type' => 'integer',
                'primary' => '1',
                'length' => '8',
            ),
            'uploadid' => 
            array(
                'type' => 'integer',
                'primary' => '1',
                'length' => '8',
            ),
            'action' => 
            array(
                'type' => 'enum',
                'values' => 
                array(
                    0 => 'CREATE',
                    1 => 'REOPEN',
                    2 => 'SUPPRESS',
                ),
                'length' => '',
            ),
        ), array(
            'primary' => 
            array(
                0 => 'vulnerabilityid',
                1 => 'uploadid',
            ),
        ));
        $this->createTable('vulnerability_xref', array(
            'vulnerability_id' => 
            array(
                'type' => 'integer',
                'primary' => '1',
                'length' => '8',
            ),
            'xref_id' => 
            array(
                'type' => 'integer',
                'primary' => '1',
                'length' => '8',
            ),
        ), array(
            'primary' => 
            array(
                0 => 'vulnerability_id',
                1 => 'xref_id',
            ),
        ));
        $this->createTable('vulnerability_audit_log', array(
            'id' => 
            array(
                'primary' => '1',
                'autoincrement' => '1',
                'type' => 'integer',
                'length' => '8',
            ),
            'createdts' => 
            array(
                'comment' => 'The timestamp when this entry was created',
                'type' => 'timestamp',
                'length' => '25',
            ),
            'message' => 
            array(
                'comment' => 'The log message',
                'type' => 'string',
                'length' => '',
            ),
            'objectid' => 
            array(
                'comment' => 'The parent object which this log entry refers to',
                'type' => 'integer',
                'length' => '8',
            ),
            'userid' => 
            array(
                'comment' => 'The user who created this log entry',
                'type' => 'integer',
                'length' => '8',
            ),
        ), array(
            'primary' => 
            array(
                0 => 'id',
            ),
        ));
        $this->createTable('vulnerability_comment', array(
            'id' => 
            array(
                'primary' => '1',
                'autoincrement' => '1',
                'type' => 'integer',
                'length' => '8',
            ),
            'createdts' => 
            array(
                'comment' => 'The timestamp when this entry was created',
                'type' => 'timestamp',
                'length' => '25',
            ),
            'comment' => 
            array(
                'comment' => 'The text of the comment',
                'type' => 'string',
                'length' => '',
            ),
            'objectid' => 
            array(
                'comment' => 'The parent object to which this comment belongs',
                'type' => 'integer',
                'length' => '8',
            ),
            'userid' => 
            array(
                'comment' => 'The user who created comment',
                'type' => 'integer',
                'length' => '8',
            ),
        ), array(
            'primary' => 
            array(
                0 => 'id',
            ),
        ));
        $this->removeColumn('finding', 'assetid');
        $this->addColumn('incident', 'deleted_at', 'timestamp', '25', array(
            'default' => '',
            'notnull' => '',
        ));
        $this->changeColumn('configuration', 'contact_phone', '15', 'string', array(
            'comment' => 'Technical support contact phone number',
            'default' => '',
            'Fisma_Doctrine_Validator_Phone' => '1',
        ));
        $this->changeColumn('incident', 'organizationid', '8', 'integer', array(
            'comment' => 'Foreign key to the affected organization/system',
            'extra' => 
            array(
                'auditLog' => '1',
                'logicalName' => 'Affected System/Organization',
            ),
        ));
    }

    public function down()
    {
        $this->dropTable('vulnerability');
        $this->dropTable('vulnerability_bugtraq');
        $this->dropTable('vulnerability_cve');
        $this->dropTable('vulnerability_resolution');
        $this->dropTable('vulnerability_upload');
        $this->dropTable('vulnerability_xref');
        $this->dropTable('vulnerability_audit_log');
        $this->dropTable('vulnerability_comment');
        $this->addColumn('finding', 'assetid', 'integer', '8', array(
            'comment' => 'Foreign key to the asset which this finding is against',
        ));
        $this->removeColumn('incident', 'deleted_at');
    }
}
