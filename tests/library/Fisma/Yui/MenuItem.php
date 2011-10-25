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
 * This test suite includes tests for
 * [..]/Yui/MenuItem.php
 * [..]/Yui/MenuItem/Goto.php
 * [..]/Yui/MenuItem/OnClick.php
 *
 * @author     Duy K. Bui <duy.bui@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Test
 * @subpackage Test_Library
 *
 */
class Test_Library_Fisma_Yui_MenuItem extends Test_Case_Unit
{
    /**
     * test constructors and accessors
     * @return void
     */
    public function testConstructors()
    {
        $event = 'onclick';
        $title = 'Test Item';
        $url = '/test';
        $object = array(
            'model' => $title,
            'controller' => $url
        );
        $onclick = new Fisma_Yui_MenuItem_OnClick($event, $object);
        $this->assertEquals($event, $onclick->fn);
        $this->assertEquals($object, $onclick->obj);
        
        $menuItem = new Fisma_Yui_MenuItem($title, $url, $onclick);
        $this->assertEquals($title, $menuItem->text);
        $this->assertEquals($url, $menuItem->url);
        $this->assertEquals($onclick, $menuItem->onclick);

        $label = 'Go To...';
        $menuGoto = new Fisma_Yui_MenuItem_GoTo($label, $title, $url);
        $this->assertEquals('Fisma.Menu.goTo', $menuGoto->onclick->fn);
        $this->assertEquals($object, $menuGoto->onclick->obj);
        $this->assertEquals($label, $menuGoto->text);
    }
}

