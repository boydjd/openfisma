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
 * A decorator which turns a text field into a date field by adding a class and some client-side behavior
 * 
 * @author     Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Zend
 */
class Fisma_Zend_Form_Decorator_Date extends Zend_Form_Decorator_Abstract
{
    /**
     * Render the element with a script immediately after that attaches the client-side date behavior
     * 
     * @param string $content
     */
    public function render($content)
    {        
        $view = Zend_Layout::getMvcInstance()->getView();
        $render .= $view->partial('form-element/date.phtml', 
                                  'default', 
                                  array('dateFieldName' => $this->getElement()->getName()));
        
        return $content . $render;
    }  
}
