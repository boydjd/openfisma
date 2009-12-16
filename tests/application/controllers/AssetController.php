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

require_once realpath(dirname(__FILE__) . '/../../FismaZendTest.php');

/**
 * Test_Application_Controllers_AssetController 
 * 
 * @package Test_Application_Controllers
 * @version $Id$
 * @copyright (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @author Josh Boyd <joshua.boyd@endeavorsystems.com> 
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Test_Application_Controllers_AssetController extends Test_FismaZendTest
{
    /**
     * testListAction 
     */
    public function testListAction()
    {
        $this->login();
        $this->dispatch('/asset/list');
        $this->assertController('asset');
        $this->assertAction('list');
    }

    /**
     * testCreateAction 
     */
    public function testCreateAction()
    {
        $this->login();
        $this->dispatch('/asset/create');
        $this->assertController('asset');
        $this->assertAction('create');
    }

    /**
     * testSearchboxAction 
     */
    public function testSearchboxAction()
    {
        $this->login();
        $this->dispatch('/asset/searchbox');
        $this->assertController('asset');
        $this->assertAction('searchbox');
    }

    /**
     * testSearchAction 
     */
    public function testSearchAction()
    {
        $this->login();
        $this->dispatch('/asset/search');
        $this->assertController('asset');
        $this->assertAction('search');
    }

    /**
     * testViewAction 
     */
    public function testViewAction()
    {
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * testDeleteAction 
     */
    public function testDeleteAction()
    {
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );

    }

    /**
     * testMultideleteAction 
     */
    public function testMultideleteAction()
    {
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );

    }
}
