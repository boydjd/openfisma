<?php
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
 * SecurityAuthorization
 * 
 * @author     Andrew Reeves <andrew.reeves@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Model
 */
class SecurityAuthorization extends BaseSecurityAuthorization
{
    /**
     * Set custom mutators
     * 
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $this->hasMutator('sysOrgId', 'setSysOrgId');
    }

    public function setSysOrgId($sysOrgId)
    {
        $this->_set('sysOrgId', $sysOrgId);

        // fetch the system and use its impact values to set the impact of this SA
        $org = Doctrine::getTable('Organization')->find($sysOrgId);
        $system = $org->System;
        if (empty($system)) {
            throw new Fisma_Exception('A non-system was set to the Security Authorization');
        }

        $impacts = array(
            $system->confidentiality,
            $system->integrity,
            $system->availability
        );
        if (in_array('HIGH', $impacts)) {
            $this->impact = 'HIGH';
        } else if (in_array('MODERATE', $impacts)) {
            $this->impact = 'MODERATE';
        } else {
            $this->impact = 'LOW';
        }
    }
}
