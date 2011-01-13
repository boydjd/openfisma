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

require_once(realpath(dirname(__FILE__) . '/../../FismaUnitTest.php'));

/**
 * Test_Application_Models_Xref 
 * 
 * @uses Test_FismaUnitTest
 * @package Test 
 * @copyright (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @author Josh Boyd <joshua.boyd@endeavorsystems.com> 
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Test_Application_Models_Xref extends Test_FismaUnitTest
{
    /**
     * testGetUrl 
     * 
     * @access public
     * @return void
     * @dataProvider xrefTypeProvider 
     */
    public function testGetUrl($expected, $value)
    {
        $xref = new Xref();
        $xref->value = $value;

        $this->assertEquals($expected, $xref->getUrl());
    }

    /**
     * xrefTypeProvider 
     * 
     * @static
     * @access public
     * @return void
     */
    static public function xrefTypeProvider()
    {
        return array(
            array('expected' => 'http://www-01.ibm.com/support/docview.wss?uid=123', 'value' => 'AIXAPAR:23'),
            array('expected' => 'http://www.securityfocus.com/bid/123', 'value' => 'BID:123'),
            array('expected' => 'http://osvdb.org/show/osvdb/123', 'value' => 'OSVDB:123'),
            array('expected' => 'http://www.securitytracker.com/id?123', 'value' => 'SECTRACK:123'),
            array('expected' => 'http://secunia.com/advisories/123', 'value' => 'SECUNIA:123'),
            array('expected' => 'http://securityreason.com/securityalert/123', 'value' => 'SREASON:123'),
            array('expected' => 'http://www.ubuntu.com/usn/123', 'value' => 'UBUNTU:123'),
            array('expected' => 'http://xforce.iss.net/xforce/xfdb/123', 'value' => 'XF:123'),
            array('expected' => null, 'value' => 'XREF:123')
        );
    }
}
