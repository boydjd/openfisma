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

require_once(realpath(dirname(__FILE__) . '/../../../../Case/Unit.php'));

/**
 * test suite for /library/Fisma/Doctrine/Validator/Url.php
 *
 * @author     Duy K. Bui <duy.bui@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Test
 * @subpackage Test_Library
 */
class Test_Library_Fisma_Doctrine_Validator_Url extends Test_Case_Unit
{
    /**
     * test validator
     * why isn't this method static?
     * @return void
     */
    public function testValidator()
    {
        $validator = new Fisma_Doctrine_Validator_Url();
        $this->assertTrue($validator->validate(null)); //not required -> true
        $this->assertFalse($validator->validate('')); //erroneous false      
        $this->assertFalse($validator->validate('a://a.a'), 'fail to detect meaningless input a://a.a -> php quirk'); 
        $this->assertTrue($validator->validate('http://xn--phnghongcung-39a120au41o.vn/'));
    }
}

