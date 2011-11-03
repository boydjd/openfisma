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

require_once (realpath(dirname(__FILE__) . '/../../Case/Unit.php'));

/**
 * Test_Application_Models_Configuration
 *
 * @uses Test_Case_Unit
 * @package Test
 * @copyright (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @author Josh Boyd <joshua.boyd@endeavorsystems.com>
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Test_Application_Models_Configuration extends Test_Case_Unit
{
    /**
     * test the invalidation of cache in preSave()
     *
     * @return void
     */
    public function testPreSave()
    {
        $mockCache = $this->getMock('BlankMock', array('remove'));
        $mockCache->expects($this->once())->method('remove')->with('configuration_key');

        $config = $this->getMock('Configuration', array('isModified', 'getModified', '_getCache'));
        $config->expects($this->exactly(2))->method('isModified')->will($this->onConsecutiveCalls(false, true));
        $config->expects($this->once())->method('_getCache')->will($this->returnValue($mockCache));
        $config->expects($this->once())->method('getModified')->will($this->returnValue(array('key' => 'value')));

        $config->preSave(null);
        $config->preSave(null);
    }

}
