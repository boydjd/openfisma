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
 * A YUI button
 *
 * @author     Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Yui
 */
class Fisma_Yui_Form_Button extends Zend_Form_Element_Submit
{
    /**
     * The YUI button type. This can be changed in subclasses to quickly create a new type of button.
     *
     * @string
     */
    protected $_yuiButtonType = 'button';

    /**
     * When this element is expressed as a string, it renders itself as a convenience. This allows the element to
     * be used as a parameter to echo, print, or string interpolation expressions.
     *
     * @return string The string expressed YUI button element
     */
    function __toString()
    {
        return $this->renderSelf();
    }

    /**
     * A default implementation of render() that creates a standard button. This is overridden in subclasses to
     * implement more unique button types.
     *
     * @return string The HTML expressed YUI button element
     */
    function renderSelf()
    {
        $view = Zend_Layout::getMvcInstance()->getView();
        return $view->partial('yui/button.phtml', array(
            'id'        => $this->getName(),
            'label'     => $this->getValue() ? $this->getValue() : $this->getLabel(),
            'imageUrl'  => $this->getAttrib('imageSrc'),
            'function'  => $this->getAttrib('onClickFunction'),
            'arguments' => $this->getAttrib('onClickArgument'),
            'disabled'  => $this->readOnly
        ));
    }
}
