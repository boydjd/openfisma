<?php
/**
 * Copyright (c) 2008 Endeavor Systems, Inc.
 *
 * This file is part of OpenFISMA.
 *
 * OpenFISMA is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * OpenFISMA is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with OpenFISMA.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 * @version   $Id$
 * @package   Form
 */

/**
 * A standard decorator that can be used for most forms in OpenFISMA in order
 * to provide a consistent look-and-feel across the application.
 *
 * @package   Form
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 * @todo rename this class to "CrudDecorator"
 */
class Form_FismaDecorator extends Zend_Form_Decorator_Abstract
{
    /**
     * buildLabel() - Create the label for this element.
     *
     * @return The label rendered in HTML.
     */
    public function buildLabel() 
    {
        $element = $this->getElement();
        $render = '';
        if (!$element instanceof Zend_Form_Element_Submit) {
            $label = '';
            if ($element->isRequired()) {
                $label .= '*';
            }
            $label .= $element->getLabel();
            if ($translator = $element->getTranslator()) {
                $label = $translator->translate($label);
            }
            $label .= ':';
            $render = $element->getView()
                              ->formLabel($element->getName(), $label);
        } else {
            $render = '&nbsp;';
        }
        return $render;
    }

    /**
     * buildInput() - Create the input control for this element.
     *
     * @return The input control rendered in HTML.
     */
    public function buildInput() 
    {
        $element = $this->getElement();
        $helper  = $element->helper;
        $value = $element->getValue();
        $render = '';
        
        if ($element->readOnly) {
            $element->setAttrib('disabled', 'disabled');
        }
        
        /* In order to render YUI elements instead of OS elements, there's a really ugly hack here. Check
         * for elements which have YUI counterparts, and then render the counterpart instead of the original
         * element. An ideal solution would be to setup plugins so that YUI elements could be used directly
         * in the form configuration file, but I can't figure out how to do that right now.
         * @todo revisit this hack
         */
        if ($element instanceof Zend_Form_Element_Submit && !$element instanceof Zend_Form_Element_Button) {
            $replacement = new Yui_Form_Button_Submit($element->getValue(), $element->getName() . "Replacement");
            $replacement->readOnly = $element->readOnly;
            $render = $replacement->__tostring();
        } elseif ($element instanceof Zend_Form_Element_Textarea && $element->readOnly) {
            $render = "<div class=\"formValue\">$value</div>";
        } else {
            $render = $element->getView()->$helper(
                $element->getName(),
                $value,
                $element->getAttribs(),
                $element->options
            );
        }
        
        return $render;
    }

    /**
     * buildErrors() - Create the error message for this element (if
     * applicable).
     *
     * @return The error message rendered in HTML.
     */
    public function buildErrors() 
    {
        $element  = $this->getElement();
        $messages = $element->getErrors();
        if (empty($messages)) {
            return '';
        }
        return '<div class="errors">'
               . $element->getView()->formErrors($messages)
               . '</div>';
    }

    /**
     * render() - Decorates the specified content with HTML table markup
     *
     * @return The element rendered in HTML.
     */
    public function render($content) 
    {
        $element = $this->getElement();
                
        // Render the HTML 4.01 strict markup for the form and form elements.
        if ($element instanceof Zend_Form_Element) {
            $render = '<tr><td>'
                    . $this->buildLabel()
                    . '</td><td>'
                    . $this->buildInput()
                    . '</td></tr>';
        } else if ($element instanceof Zend_Form_DisplayGroup) {
            $render = '<div class=\'subform\'><table class=\'fisma_crud\'>'
                    . $content
                    . '</table></div>';
            
        } else if ($element instanceof Zend_Form) {
            $enctype = $element->getAttrib('enctype');
            $id      = $element->getAttrib('id');

            if ($element->isReadOnly()) {
                $render = '<div class=\'form\'>'
                        . $content
                        . '</div>';            
            } else {
                $render = "<form method='{$element->getMethod()}'"
                        . " action='{$element->getAction()}'"
                        . (isset($enctype) ? " enctype=\"$enctype\"" : '')
                        . (isset($id) ? " id=\"$id\"" : '')
                        . '>'
                        . '<div class=\'form\'>'
                        . $content
                        . '</div>'
                        . '</form>';
            }
        } else {
            throw new Exception_General("The element to be rendered is an unknown"
                                    . " class: "
                                    . get_class($element));
        }
        
        return $render;
    }
}
