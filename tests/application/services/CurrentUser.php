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
 * Test_Application_Services_CurrentUser
 * 
 * @uses Test_Case_Unit
 * @package Test 
 * @copyright (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @author Josh Boyd <joshua.boyd@endeavorsystems.com> 
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Test_Application_Services_CurrentUser extends Test_Case_Unit
{
    /**
     * testSet 
     * 
     * @access public
     * @return void
     */
    public function testSet()
    {
        $currentUser = new Application_Service_CurrentUser();
        $this->assertAttributeEmpty('_user', $currentUser);
        $currentUser->set(new User());
        $this->assertAttributeInstanceOf('User', '_user', $currentUser);
    }

    /**
     * testGet 
     * 
     * @access public
     * @return void
     */
    public function testGet()
    {
        $currentUser = new Application_Service_CurrentUser();
        $currentUser->set(new User());
        $this->assertInstanceOf('User', $currentUser->get());
    }
}
