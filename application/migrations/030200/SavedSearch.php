<?php
/**
 * Copyright (c) 2013 Endeavor Systems, Inc.
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
 * Add Saved Searches as defined in OFJ-2094
 *
 * @author     Duy K. Bui <duy.bui@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2013 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Migration
 */

class Application_Migration_030200_SavedSearch extends Fisma_Migration_Abstract
{
    /**
     * Migrate.
     */
    public function migrate()
    {
        $this->message("Creating Query table...");
        $models = array(
            'Finding',
            'Source',
            'Vulnerability',
            'VulnerabilityResolution',
            'Asset',
            'Incident',
            'IrCategory',
            'IrWorkflow',
            'SecurittyControl',
            'SecurittyControlCatalog',
            'SystemDocument',
            'DocumentType',
            'Network',
            'Organization',
            'OrganizationType',
            'System',
            'SystemType',
            'User',
            'Role'
        )
        $this->getHelper()->createTable(
            'query',
            array(
                'id' => 'bigint(20) NOT NULL AUTO_INCREMENT',
                'createdts' => 'datetime NOT NULL',
                'modifiedts' => 'datetime NOT NULL',
                'creatorid' => 'bigint(20)',
                'model' => "enum('" . implode("','", $models) . "')",
                'name' => 'text',
                'url' => 'text',
                'sharedorganizationid' => 'bigint(20)'
            ),
            'id'
        );
        $this->message("Creating foreign keys...");
        $this->getHelper()->addForeignKey('query', 'creatorid', 'user', 'id');
        $this->getHelper()->addForeignKey('query', 'sharedorganizationid', 'organization', 'id');
    }
}
