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
 * Tests for Fisma_Version.
 *
 * @author     Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Test
 * @subpackage Test_Fisma
 */
class Test_Library_Fisma_Version extends Test_Case_Unit
{
    /**
     * Test the constructor and getters.
     */
    public function testConstructorAndGetters()
    {
        $version = new Fisma_Version(2,17,0);

        $this->assertSame(2, $version->getMajorVersion());
        $this->assertSame(17, $version->getMinorVersion());
        $this->assertSame(0, $version->getTagNumber());

        $this->assertEquals("2.17.0", $version->getDottedString());
        $this->assertEquals("021700", $version->getPaddedString());
    }

    /**
     * Test invalid version number.
     *
     * @expectedException Fisma_Zend_Exception
     */
    public function testInvalidVersionNumber()
    {
        $version = new Fisma_Version("foo", "bar", "foobar");
    }

    /**
     * Test dotted version factory method.
     */
    public function testDottedVersion()
    {
        $dottedVersion = Fisma_Version::createVersionFromDottedString("2.17.0");

        $this->assertSame(2, $dottedVersion->getMajorVersion());
        $this->assertSame(17, $dottedVersion->getMinorVersion());
        $this->assertSame(0, $dottedVersion->getTagNumber());

        $this->assertEquals("021700", $dottedVersion->getPaddedString());
    }

    /**
     * Test invalid dotted version string.
     *
     * @expectedException Fisma_Zend_Exception
     */
    public function testInvalidDottedVersion()
    {
        $dottedVersion = Fisma_Version::createVersionFromDottedString("2.17.0.1");
    }

    /**
     * Test padded version factory method.
     */
    public function testPaddedVersion()
    {
        $paddedVersion = Fisma_Version::createVersionFromPaddedString("021700");

        $this->assertSame(2, $paddedVersion->getMajorVersion());
        $this->assertSame(17, $paddedVersion->getMinorVersion());
        $this->assertSame(0, $paddedVersion->getTagNumber());

        $this->assertEquals("2.17.0", $paddedVersion->getDottedString());
    }

    /**
     * Test invalid padded version string.
     *
     * @expectedException Fisma_Zend_Exception
     */
    public function testInvalidPaddedVersion()
    {
        $paddedVersion = Fisma_Version::createVersionFromPaddedString("02170000");
    }
}
