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

require_once(realpath(dirname(__FILE__) . '/../../../../../Case/Unit.php'));

/**
 * Test_Application_Modules_Default_Views_Helpers_Acl
 * 
 * @uses Test_Case_Unit
 * @package Test 
 * @copyright (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @author Josh Boyd <joshua.boyd@endeavorsystems.com> 
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Test_Application_Modules_Default_Views_Helpers_Acl extends Test_Case_Unit
{
    /**
     * _helper 
     * 
     * @var View_Helper_Acl 
     * @access private
     */
    private $_helper;

    /**
     * setUp 
     * 
     * @access public
     * @return void
     */
    public function setUp()
    {
        require_once(
            realpath(dirname(__FILE__) . '/../../../../../../application/modules/default/views/helpers/Acl.php')
        );
        $this->_helper = new View_Helper_Acl();
    }

    /**
     * testGetNullAcl 
     * 
     * @access public
     * @return void
     */
    public function testGetNullAcl()
    {
        $this->assertNull($this->_helper->acl());
    }

    /**
     * testSetAcl 
     * 
     * @access public
     * @return void
     */
    public function testSetAcl()
    {
        $this->_helper->setAcl(new Fisma_Zend_Acl(null));
        $this->assertInstanceOf('Fisma_Zend_Acl', $this->_helper->acl());
    }
}
