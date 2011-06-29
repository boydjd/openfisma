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
 * Tests for the Fisma_Chart class
 *
 * @author     Dale Frey
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Test
 * @subpackage Test_Fisma
 */
class Test_Library_Fisma_Chart extends Test_FismaUnitTest
{
    public function testConvertFromStackedToRegular()
    {
        $chart = new Fisma_Chart(200, 300, 'uniqueIdHere', '/my/external/source/URL');
        $chart
            ->setChartType('stackedbar')
            ->setLayerLabels(array('HIGH', 'MODERATE', 'LOW'))
            ->addColumn('My Column Label', array(1, 2, 3))
            ->addColumn('My Column Label', array(1, 2, 3))
            ->addColumn('My Column Label', array(1, 2, 3));

        $this->assertEquals($chart->getChartType(), 'stackedbar');

        $chart->convertFromStackedToRegular();

        $this->assertEquals($chart->getChartType(), 'bar');
        $this->assertEquals($chart->getColumnCount(), 3);
        $this->assertFalse($chart->isStacked());

    }

    public function testAddColumn()
    {
        $chart = new Fisma_Chart(200, 300, 'uniqueIdHere', '/my/external/source/URL');

        // Should rasied an exception - cannot add a column without a setChartType() called previously
        try {
            $chart->addColumn('My Column Label', 7, 'http://www.google.com');
            $this->fail();
        } catch (Exception $e)  {
            // exception expected, moving on...
        }

        $chart->setChartType('bar');

        // Should rasied an exception - cannot add an array of numbers to a non-stacked (regular) bar chart.
        try {
            $chart->addColumn('My Column Label', array(7, 3, 5), 'http://www.google.com');
            $this->fail();
        } catch (Exception $e)  {
            // exception expected, moving on...
        }

        $chart->setChartType('stackedbar');
        $chart->setLayerLabels(array('HIGH', 'MODERATE', 'LOW'));

        // Should rasied an exception - cannot add an integer to a stacked-bar chart.
        try {
            $chart->addColumn('My Column Label', 7, 'http://www.google.com');
            $this->fail();
        } catch (Exception $e)  {
            // exception expected, moving on...
        }
    }

    public function testIsStacked()
    {
        $chart = new Fisma_Chart(200, 300, 'uniqueIdHere', '/my/external/source/URL');

        $chart->setChartType('bar');
        $this->assertFalse($chart->isStacked());

        $chart->setChartType('pie');
        $this->assertFalse($chart->isStacked());

        $chart->setChartType('line');
        $this->assertFalse($chart->isStacked());

        $chart->setChartType('stackedbar');
        $this->assertTrue($chart->isStacked());

        $chart->setChartType('stackedline');
        $this->assertTrue($chart->isStacked());

    }

    public function testSetChartType()
    {
        $chart = new Fisma_Chart(200, 300, 'uniqueIdHere', '/my/external/source/URL');
        $chart->setChartType('bar');

        $this->assertEquals('bar', $chart->getChartType());

    }
}
