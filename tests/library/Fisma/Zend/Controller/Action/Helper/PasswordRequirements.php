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

require_once(realpath(dirname(__FILE__) . '/../../../../../../FismaUnitTest.php'));

/**
 * Test_Library_Fisma_Zend_Controller_Action_Helper_PasswordRequirements 
 * 
 * @uses Test_FismaUnitTest
 * @package Test_Library_Fisma_Zend_Controller_Action_Helper 
 * @copyright (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @author Josh Boyd <joshua.boyd@endeavorsystems.com> 
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Test_Library_Fisma_Zend_Controller_Action_Helper_PasswordRequirements extends Test_FismaUnitTest
{
    /**
     * setUp 
     * 
     * @access public
     * @return void
     */
    public function setUp()
    {
        Fisma::setConfiguration(new Fisma_Configuration_Array(), true);
    }

    /**
     * testAllRequirements 
     * 
     * @access public
     * @return void
     */
    public function testAllRequirements()
    {
        Fisma::configuration()->setConfig('pass_min_length', 1);
        Fisma::configuration()->setConfig('pass_uppercase', 1);
        Fisma::configuration()->setConfig('pass_lowercase', 1);
        Fisma::configuration()->setConfig('pass_numerical', 1);
        Fisma::configuration()->setConfig('pass_special', 1);

        $passwordRequirements = $this->_getPasswordRequirements();

        $this->assertEquals(5, count($passwordRequirements));
    }

    /**
     * testMinOnly 
     * 
     * @access public
     * @return void
     */
    public function testMinOnly()
    {
        Fisma::configuration()->setConfig('pass_min_length', 1);

        $passwordRequirements = $this->_getPasswordRequirements();

        $this->assertEquals(1, count($passwordRequirements));
    }

    /**
     * _getPasswordRequirements 
     * 
     * @access private
     * @return void
     */
    private function _getPasswordRequirements()
    {
        $passwordRequirements = new Fisma_Zend_Controller_Action_Helper_PasswordRequirements();
        return $passwordRequirements->direct();
    } 
}
