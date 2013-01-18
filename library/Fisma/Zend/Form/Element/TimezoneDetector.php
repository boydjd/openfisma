<?php
/**
 * Copyright (c) 2012 Endeavor Systems, Inc.
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
 * Renders a hidden timezone detector
 *
 * @author     Duy K. Bui <duy.bui@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2012 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Zend_Form
 */
class Fisma_Zend_Form_Element_TimezoneDetector extends Zend_Form_Element_Hidden
{
    /**
     * Renders the checkbox tree into a table.
     *
     * @param Zend_View_Interface $view Provided for compatibility
     * @return string The rendered checkbox tree in HTML
     */
    function render(Zend_View_Interface $view = null)
    {
        $render = parent::render($view)
                . '<script>$(function(){$("input[name='
                . $this->getName()
                . ']").val(Fisma.Util.getTimezone());});</script>';

        return $render;
    }
}
