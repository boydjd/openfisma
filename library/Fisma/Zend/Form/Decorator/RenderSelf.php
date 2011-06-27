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
class Fisma_Zend_Form_Decorator_RenderSelf extends Zend_Form_Decorator_Abstract
{
    /**
     * Allow an element to render itself, if it is capable of doing so.
     * 
     * Otherwise, it will return the same content passed in
     * 
     * @param string $content
     */
    public function render($content)
    {
        $render = $content;
        
        // Replace content with the element's own self-rendering, if it has a renderSelf method
        $element = $this->getElement();
        if (method_exists($element, 'renderSelf')) {
            $render = $element->renderSelf();
        }
        
        return $render;
    }
}
