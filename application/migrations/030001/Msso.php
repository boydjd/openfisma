<?php
/**
 * Copyright (c) 2012 Endeavor Systems, Inc.
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
 * Add MSSO changes as defined in OFJ-1991, OFJ-1992, OFJ-1994, OFJ-1995, OFJ-1996
 *
 * @author     Duy K. Bui <duy.bui@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2012 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Migration
 */
class Application_Migration_030001_Msso extends Fisma_Migration_Abstract
{
    /**
     * Migrate.
     */
    public function migrate()
    {
        $this->message("Adding Optional fields...");
        $this->getHelper()->addColumn(
            'configuration',
            'optionalfields',
            'text',
            'backgroundtasks'
        );
        $this->getHelper()->dropColumn(
            'configuration',
            'use_legacy_finding_key'
        );

        $this->message("Adding Organization POC List...");
        $this->getHelper()->addColumn(
            'configuration',
            'organization_poc_list',
            'text',
            'system_name'
        );
        $this->getHelper()->exec(
            "UPDATE configuration SET `organization_poc_list` = 'Business Owner,Information Security Officer,Authoriz" .
            "ing Official';"
        );
        $this->getHelper()->createTable(
            'organization_poc',
            array(
                'id' => 'bigint(20) not null auto_increment',
                'pocid' => 'biggint(20)',
                'type' => 'varchar(255)',
                'objectid' => 'bigint(20)'
            ),
            'id'
        );

        $this->message("Adding Finding Link Types...");
        $this->getHelper()->addColumn(
            'configuration',
            'finding_link_types',
            'text',
            'finding_draft_due'
        );
        $this->getHelper()->exec(
            "UPDATE configuration SET `finding_link_types` = 'is a repeat of,duplicates/is duplicated by,fixes/is fix" .
            "ed by';"
        );
        $this->getHelper()->createTable(
            'finding_relationship',
            array(
                'id' => 'bigint(20) not null auto_increment',
                'createdts' => 'datetime not null',
                'modifiedts' => 'datetime not null',
                'startfindingid' => 'bigint(20)',
                'endfindingid' => 'bigint(20)',
                'relationship' => 'varchar(255)',
                'createdbyuserid' => 'biggint(20)'
            ),
            'id'
        );

        $this->message("Adding Finding Audit Year...");
        $this->getHelper()->addColumn(
            'finding',
            'audityear',
            'varchar(4)',
            'discovereddate'
        );

        $this->message("Adding System FISMA Reportable...");
        $this->getHelper()->addColumn(
            'system',
            'fismareportable',
            'tinyint(1)',
            'fipscategory'
        );

        $this->message("Adding System Next SA Date...");
        $this->getHelper()->addColumn(
            'system',
            'nextsecurityauthorizationdt',
            'date',
            'fismareportable'
        );

        //@TODO: add foreign key constraints

        //@TODO: add privileges and assign to roles
    }
}

