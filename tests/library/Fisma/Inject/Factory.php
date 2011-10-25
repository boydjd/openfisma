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
 * test /library/Fisma/Inject/Factory.php
 *
 * @author     Duy K. Bui <duy.bui@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Test
 * @subpackage Test_Library
 */
class Test_Library_Fisma_Inject_Factory extends Test_Case_Unit
{
    /**
     * test type validation, exception thrown by private _validateType()
     * @return void
     */
    public function testNotDetectableType()
    {
        $this->setExpectedException('Fisma_Zend_Exception_InvalidFileFormat');
        Fisma_Inject_Factory::create(true, null);
    }

    /**
     * test type validation, exception thrown by constructor, failing to create class
     * @return void
     */
    public function testInvalidType()
    {
        $this->setExpectedException('Fisma_Zend_Exception');
        Fisma_Inject_Factory::create('Test', null);
    }

    /**
     * test type validation, exception thrown by constructor, recognizing class does not extends Abstract
     * couldn't be done with current code structure
     * @return void
    public function testUnsupportedType()
    {
        $this->setExpectedException('Fisma_Inject_Exception');
    }
     */
}

