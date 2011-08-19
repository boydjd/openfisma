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
 * Adds/drops the tables for the Bugtraq, Xref, and Cve models
 * Adds/drops columns to Finding for CVSS items
 * 
 * @package Migration 
 * @copyright (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @author Josh Boyd <joshua.boyd@endeavorsystems.com> 
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Version19 extends Doctrine_Migration_Base
{
    public function up()
    {
        $this->createTable(
            'bugtraq', array(
             'id' => 
             array(
              'type' => 'integer',
              'length' => '8',
              'autoincrement' => '1',
              'primary' => '1',
             ),
             'value' => 
             array(
              'type' => 'integer',
              'unsigned' => '1',
              'length' => '4',
             ),
             ), array(
             'primary' => 
             array(
              0 => 'id',
             ),
             )
        );
        $this->createTable(
            'cve', array(
             'id' => 
             array(
              'type' => 'integer',
              'length' => '8',
              'autoincrement' => '1',
              'primary' => '1',
             ),
             'value' => 
             array(
              'type' => 'string',
              'length' => '255',
             ),
             ), array(
             'primary' => 
             array(
              0 => 'id',
             ),
             )
        );
        $this->createTable(
            'finding_bugtraq', array(
             'finding_id' => 
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
              0 => 'finding_id',
              1 => 'bugtraq_id',
             ),
             )
        );
        $this->createTable(
            'finding_cve', array(
             'finding_id' => 
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
              0 => 'finding_id',
              1 => 'cve_id',
             ),
             )
        );
        $this->createTable(
            'finding_xref', array(
             'finding_id' => 
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
              0 => 'finding_id',
              1 => 'xref_id',
             ),
             )
        );
        $this->createTable(
            'xref', array(
             'id' => 
             array(
              'type' => 'integer',
              'length' => '8',
              'autoincrement' => '1',
              'primary' => '1',
             ),
             'value' => 
             array(
              'type' => 'string',
              'comment' => 'External reference provided by Nessus',
              'length' => '255',
             ),
             ), array(
             'primary' => 
             array(
              0 => 'id',
             ),
             )
        );
        $this->addColumn(
            'finding', 'cvssbasescore', 'float', '', array(
             'comment' => 'The CVSS Base Score of the finding',
             'extra' => 
             array(
              'auditLog' => '1',
              'logicalName' => 'CVSS Base Score',
              'searchIndex' => 'unstored',
             ),
             )
        );
        $this->addColumn(
            'finding', 'cvssvector', 'string', '255', array(
             'comment' => 'The CVSS Vector of the finding',
             'extra' => 
             array(
              'auditLog' => '1',
              'logicalName' => 'CVSS Vector',
              'searchIndex' => 'unstored',
             ),
             )
        );
    }

    public function down()
    {
        $this->dropTable('bugtraq');
        $this->dropTable('cve');
        $this->dropTable('finding_bugtraq');
        $this->dropTable('finding_cve');
        $this->dropTable('finding_xref');
        $this->dropTable('xref');
        $this->removeColumn('finding', 'cvssbasescore');
        $this->removeColumn('finding', 'cvssvector');
    }
}
