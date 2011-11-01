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

require_once(realpath(dirname(__FILE__) . '/../../Case/Unit.php'));

/**
 * Test_Application_Models_Asset 
 * 
 * @uses Test_Case_Unit
 * @package Test
 * @copyright (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @author Josh Boyd <joshua.boyd@endeavorsystems.com> 
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Test_Application_Models_Asset extends Test_Case_Unit
{
    /**
     * testGetOrganizationDependencyId 
     * 
     * @access public
     * @return void
     */
    public function testGetOrganizationDependencyId()
    {
       $asset = new Asset();
       $asset->orgSystemId = 1;

       $this->assertEquals(1, $asset->getOrganizationDependencyId());
    }

    /**
     * Test preDelete()
     * 
     * @return void
     */
    public function testPreDelete()
    {
        $asset = new Asset();
        $asset->preDelete(null); // as Vulnerabilities array is empty, no exception thrown

//        $mockVulnerability = $this->getMock('Doctrine_Collection', array('set'), array(null))->expects($this->once())->method('set');
        $mockVulnerability = new AssetMockVulnerability();
        $asset->Vulnerabilities[] = $mockVulnerability;
        $this->setExpectedException('Fisma_Zend_Exception_User', 'This asset cannot be deleted because it has vulnerabilities against it');
        $asset->preDelete(null); // expecteds exception as Vulnerability has been added
    }
}
/**
 * A mock up of Vulnerability to test preDelete()
 * 
 */
class AssetMockVulnerability
{
    /**
     * A dummy function called by Doctrine_Collection
     * 
     * @param string $field 
     * @param Asset $value 
     * @param bool   $lock  
     * 
     * @return bool
     */
    public function set($field, Asset $value, $lock)
    {
        return true;
    }
}
