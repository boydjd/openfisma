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
 * Tests for Fisma_AsyncResponse
 *
 * @author     Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Test
 * @subpackage Test_Library
 */
class Test_Library_Fisma_AsyncResponse extends Test_Case_Unit
{
   /**
    * Test a successful async response
    * @return void
    */
   public function testSucceed()
   {
       $testMessage = 'TEST MESSAGE';
       $response = new Fisma_AsyncResponse;

       $response->succeed($testMessage);

       $this->assertTrue($response->success);
       $this->assertEquals($response->message, $testMessage);
   }

   /**
    * Test a failed async response
    * @return void
    */
   public function testFail()
   {
       $testMessage = 'TEST MESSAGE';
       $response = new Fisma_AsyncResponse;

       $response->fail($testMessage);

       $this->assertFalse($response->success);
       $this->assertEquals($response->message, $testMessage);
   }
}
