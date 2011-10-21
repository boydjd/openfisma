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

require_once(realpath(dirname(__FILE__) . '/../../../Case/Unit.php'));

/**
 * Class description
 *
 * @author     Duy K. Bui <duy.bui@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Test
 * @subpackage Test_Library
 */
class Test_Library_Fisma_Zend_Form extends Test_Case_Unit
{
    /**
     * test the readOnly overriding
     */
    public function testRender()
    {
        require_once(realpath(dirname(__FILE__) . '/ElementDummy.php'));
        $testForm = new Fisma_Zend_Form();
        $testElement = new Test_Library_Fisma_Zend_ElementDummy();
        $testForm->addElement($testElement);
        $testForm->setReadOnly(true);
        //bypassing Exception thrown by "Fisma_Zend_Form::parent::render()"
        try {
            $testForm->render();
        }
        catch (Zend_Form_Decorator_Exception $expected) {
            $this->assertTrue($testElement->readOnly);
        }
    }

    /**
     * test the setter/getter of readOnly
     */
    public function testReadOnly()
    {
        $testForm = new Fisma_Zend_Form();
        $testForm->setReadOnly(true);
        $this->assertTrue($testForm->isReadOnly());

        $value = -1;
        $this->setExpectedException('Fisma_Zend_Exception', 'Invalid type for \''.$value.'\'. Expected a boolean.');
        $testForm->setReadonly($value);
    }
}
