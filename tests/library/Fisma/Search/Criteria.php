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
 * test /library/Fisma/Search/Criteria.php
 *
 * @author     Duy K. Bui <duy.bui@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Test
 * @subpackage Test_Library
 */
class Test_Library_Fisma_Search_Criteria extends Test_Case_Unit
{
    /**
     * test everything
     * wouldn't make sense to separate tests because all previous steps must be redone
     * @require Fisma_Search_Criterion
     * @return void
     */
    public function test()
    {
        $criterion = new Fisma_Search_Criterion('id', 'integerLessThan', array(0 => 10));
        $criteria = new Fisma_Search_Criteria();
        $criteria->add($criterion);
        $criteria->rewind();
        $this->assertEquals(0, $criteria->key());
        $this->assertTrue($criteria->valid());
        $this->assertEquals($criterion, $criteria->current());
        $criteria->next();
        $this->assertFalse($criteria->valid());
    }
}

