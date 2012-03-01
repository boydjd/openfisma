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

require_once(realpath(dirname(__FILE__) . '/../../Case/Unit.php'));

/**
 * Test_Application_Models_Mail
 * 
 * @uses Test_Case_Unit
 * @package Test
 * @copyright (c) Endeavor Systems, Inc. 2012 {@link http://www.endeavorsystems.com}
 * @author Ben Zheng <ben.zheng@reyosoft.com>
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Test_Application_Models_Mail extends Test_Case_Unit
{
    /**
     * Set up configuration items by sender and system_name
     * 
     * @access public
     * @return void
     */
    public function setUp()
    {
        Fisma::setConfiguration(new Fisma_Configuration_Array(), true);

        Fisma::configuration()->setConfig('sender', 'test@openfisma.org');
        Fisma::configuration()->setConfig('system_name', 'OpenFISMA');
    }

    /**
     * Test default sender and sender name
     * 
     * @access public
     * @return void
     */
    public function testSenderAndSenderName()
    {
        $mail = new Mail();

        $this->assertEquals('test@openfisma.org', $mail->sender);
        $this->assertEquals('OpenFISMA', $mail->senderName);
    }

    /**
     * Test for Zend_Mail object
     * 
     * @access public
     * @return void
     */
    public function testToZendMail()
    {
        $mail = new Mail();

        $zendMail = $mail->toZendMail();

        $this->assertTrue($zendMail instanceof Zend_Mail);
    }
}
