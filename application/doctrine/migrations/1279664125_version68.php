<?php
// @codingStandardsIgnoreFile
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
 * Add relation from Incident model to Organization model
 * 
 * @uses Doctrine_Migration_Base
 * @package Migration 
 * @copyright (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @author Mark E. Haase <mhaase@endeavorsystems.com>
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Version68 extends Doctrine_Migration_Base
{
    /**
     * Add column and foreign key
     */
    public function up()
    {
		$this->addColumn('incident', 'organizationid', 'integer', '8', array(
             'comment' => 'Foreign key to the affected organization/system',
             ));

		$this->createForeignKey('incident', 'incident_organizationid_organization_id', array(
             'name' => 'incident_organizationid_organization_id',
             'local' => 'organizationid',
             'foreign' => 'id',
             'foreignTable' => 'organization',
             ));

		$this->addIndex('incident', 'incident_organizationid', array(
             'fields' => 
             array(
              0 => 'organizationid',
             ),
             ));

    }

    /**
     * Drop foreign key and column
     */
    public function down()
    {
		$this->removeIndex('incident', 'incident_organizationid', array(
             'fields' => 
             array(
              0 => 'organizationid',
             ),
             ));

		$this->dropForeignKey('incident', 'incident_organizationid_organization_id');

 		$this->removeColumn('incident', 'organizationid');
    }
}
