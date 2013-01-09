<?php
/**
 * Copyright (c) 2008 Endeavor Systems, Inc.
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
 * A YUI button for submitting forms
 *
 * @author     Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Yui
 */
class Fisma_Yui_Form_Button_Submit extends Fisma_Yui_Form_Button
{
    /**
     * Instead of overriding render(), renderSelf() can be called by the decorator to build the input.
     * This saves the trouble of creating a separate view helper and allows the element to simply draw
     * itself.
     *
     * @return string The HTML snippet of the YUI submit button rendered
     */
    function renderSelf()
    {
        $view = Zend_Layout::getMvcInstance()->getView();
        return $view->partial('yui/button-submit.phtml', array(
            'id'        => $this->getName(),
            'label'     => $this->getLabel(),
            'value'     => ($this->getValue()) ? $this->getValue() : $this->getName(),
            'imageUrl'  => $this->getAttrib('imageSrc'),
            'function'  => $this->getAttrib('onClickFunction'),
            'arguments' => $this->getAttrib('onClickArgument'),
            'disabled'  => $this->readOnly
        ));
    }
}
