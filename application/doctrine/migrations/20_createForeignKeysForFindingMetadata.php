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
 * CreateForiegnKeysForFindingMetadata 
 * 
 * @package Migration
 * @version $Id$
 * @copyright (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @author Josh Boyd <joshua.boyd@endeavorsystems.com> 
 * @license http://www.openfisma.org/content/license GPLv3
 */
class CreateForiegnKeysForFindingMetadata extends Doctrine_Migration_Base
{
    /**
     * Create foreign keys and indexes
     */
    public function up()
    {
        $this->createForeignKey(
            'finding_bugtraq', 'finding_bugtraq_bugtraq_id_bugtraq_id', array(
                    'name' => 'finding_bugtraq_bugtraq_id_bugtraq_id',
                    'local' => 'bugtraq_id',
                    'foreign' => 'id',
                    'foreignTable' => 'bugtraq',
                    )
        );
        $this->createForeignKey(
            'finding_bugtraq', 'finding_bugtraq_finding_id_finding_id', array(
                    'name' => 'finding_bugtraq_finding_id_finding_id',
                    'local' => 'finding_id',
                    'foreign' => 'id',
                    'foreignTable' => 'finding',
                    )
        );
        $this->createForeignKey(
            'finding_cve', 'finding_cve_cve_id_cve_id', array(
                    'name' => 'finding_cve_cve_id_cve_id',
                    'local' => 'cve_id',
                    'foreign' => 'id',
                    'foreignTable' => 'cve',
                    )
        );
        $this->createForeignKey(
            'finding_cve', 'finding_cve_finding_id_finding_id', array(
                    'name' => 'finding_cve_finding_id_finding_id',
                    'local' => 'finding_id',
                    'foreign' => 'id',
                    'foreignTable' => 'finding',
                    )
        );
        $this->createForeignKey(
            'finding_xref', 'finding_xref_finding_id_finding_id', array(
                    'name' => 'finding_xref_finding_id_finding_id',
                    'local' => 'finding_id',
                    'foreign' => 'id',
                    'foreignTable' => 'finding',
                    )
        );
        $this->addIndex(
            'finding_bugtraq', 'finding_bugtraq_bugtraq_id', array(
                    'fields' => 
                    array(
                        0 => 'bugtraq_id',
                        ),
                    )
        );
        $this->addIndex(
            'finding_bugtraq', 'finding_bugtraq_finding_id', array(
                    'fields' => 
                    array(
                        0 => 'finding_id',
                        ),
                    )
        );
        $this->addIndex(
            'finding_cve', 'finding_cve_cve_id', array(
                    'fields' => 
                    array(
                        0 => 'cve_id',
                        ),
                    )
        );
        $this->addIndex(
            'finding_cve', 'finding_cve_finding_id', array(
                    'fields' => 
                    array(
                        0 => 'finding_id',
                        ),
                    )
        );
        $this->addIndex(
            'finding_xref', 'finding_xref_finding_id', array(
                    'fields' => 
                    array(
                        0 => 'finding_id',
                        ),
                    )
        );
    }

    /**
     * Drop foreign keys and indexes 
     */
    public function down()
    {
        $this->dropForeignKey('finding_bugtraq', 'finding_bugtraq_bugtraq_id_bugtraq_id');
        $this->dropForeignKey('finding_bugtraq', 'finding_bugtraq_finding_id_finding_id');
        $this->dropForeignKey('finding_cve', 'finding_cve_cve_id_cve_id');
        $this->dropForeignKey('finding_cve', 'finding_cve_finding_id_finding_id');
        $this->dropForeignKey('finding_xref', 'finding_xref_finding_id_finding_id');
        $this->removeIndex(
            'finding_bugtraq', 'finding_bugtraq_bugtraq_id', array(
                    'fields' => 
                    array(
                        0 => 'bugtraq_id',
                        ),
                    )
        );
        $this->removeIndex(
            'finding_bugtraq', 'finding_bugtraq_finding_id', array(
                    'fields' => 
                    array(
                        0 => 'finding_id',
                        ),
                    )
        );
        $this->removeIndex(
            'finding_cve', 'finding_cve_cve_id', array(
                    'fields' => 
                    array(
                        0 => 'cve_id',
                        ),
                    )
        );
        $this->removeIndex(
            'finding_cve', 'finding_cve_finding_id', array(
                    'fields' => 
                    array(
                        0 => 'finding_id',
                        ),
                    )
        );
        $this->removeIndex(
            'finding_xref', 'finding_xref_finding_id', array(
                    'fields' => 
                    array(
                        0 => 'finding_id',
                        ),
                    )
        );
    }
}
