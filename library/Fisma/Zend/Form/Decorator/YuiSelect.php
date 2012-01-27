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

/**
 * A decorator which lets an element render itself, if the element has a renderSelf() method
 *
 * @author     Mark E. Haase
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Zend
 */
class Fisma_Zend_Form_Decorator_YuiSelect extends Zend_Form_Decorator_Abstract
{
    /**
     * Wrap the select element in YUI Button Menu
     *
     * @param string $content The select HTML markup
     */
    public function render($content)
    {
        $name = $this->getElement()->getName();
        $chunks = explode('<select', $content);
        $render = "{$chunks[0]}<input type='button' id='{$name}-button'/><select{$chunks[1]}
                   <script>
                       YAHOO.util.Event.onContentReady('$name-button', function () {
                           var selectElement = document.getElementById('{$name}');
                           var selectedLabel = selectElement.options[selectElement.selectedIndex].innerHTML;
                           var oMenuButton = new YAHOO.widget.Button('{$name}-button', {
                               label: selectedLabel,
                               type: 'menu',
                               menu: '{$name}'
                           });
                           oMenuButton.getMenu().subscribe('click', function (p_sType, p_aArgs) {
                               if (p_aArgs[1]) {
                                   oMenuButton.set('label', p_aArgs[1].cfg.getProperty('text'));
                               }
                           });
                       });
                   </script>";

        return $render;
    }
}
