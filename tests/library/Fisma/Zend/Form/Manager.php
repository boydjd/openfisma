<?php
/**
 * Copyright (c) 2010 Endeavor Systems, Inc.
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

require_once(realpath(dirname(__FILE__) . '/../../../../FismaUnitTest.php'));

/**
 * Tests for the Fisma_Zend_Form_Manager class
 * 
 * @author     Andrew Reeves <andrew.reeves@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Test
 * @subpackage Test_Fisma_Zend_Form_Element
 */
class Test_Library_Fisma_Zend_Form_Manager extends Test_FismaUnitTest
{
    /**
     * Test that the prepareForm static method.
     *
     * @todo Add checks for proper initialization of decorators.
     */
    public function testPrepareForm()
    {
        // test that the StringTrim filter is added to each form element
        $form = new Fisma_Zend_Form('myform');
        $form->addElement(new Zend_Form_Element('myelem'));
        $formResult = Fisma_Zend_Form_Manager::prepareForm($form);
        foreach ($formResult->getElements() as $element) {
            $hasFilter = false;
            foreach ($element->getFilters() as $filter) {
                if ($filter == 'StringTrim' || $filter instanceOf Zend_Filter_StringTrim) {
                    $hasFilter = true;
                    break;
                }
            }
            $this->assertTrue($hasFilter, 'Element found with StringTrim filter.');
        }
    }
}
