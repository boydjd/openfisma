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
 * <http://www.gnu.org/licenses/>.
 */

/**
 * A standard decorator that can be used for most forms in OpenFISMA in order
 * to provide a consistent look-and-feel across the application.
 * 
 * @author     Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2009 (http://www.endeavorsystems.com)
 * @license    http://www.openfisma.org/content/license
 * @package    Fisma
 * @subpackage Fisma_Form
 * @version    $Id$
 */
class Fisma_Form_FismaDecorator extends Zend_Form_Decorator_Abstract
                                implements Zend_Form_Decorator_Marker_File_Interface
{
    /**
     * buildLabel() - Create the label for this element.
     *
     * @return The label rendered in HTML.
     */
    public function buildLabel() 
    {
        $element = $this->getElement();
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
            
            $attrib = array();
            $render = $element->getView()
                              ->formLabel($element->getName(), $label);
            if (isset($element->tooltip)) {
                $render = "<span id='{$element->getName()}Tooltip' class='tooltip'>$render</span>"
                        . '<script type="text/javascript">'
                        . "{$element->getName()}Tooltip = new YAHOO.widget.Tooltip("
                        . "\"{$element->getName()}TooltipObj\", { context:\"{$element->getName()}Tooltip\", "
                        . "showdelay: 150, hidedelay: 150, autodismissdelay: 25000, "
                        . "text:\"{$element->tooltip}\", "
                        . 'effect:{effect:YAHOO.widget.ContainerEffect.FADE,duration:0.25}, '
                        . 'width: "50%"});'
                        . '</script>';
            }
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

        $_buttonTypes = array(
            'Zend_Form_Element_Button',
            'Zend_Form_Element_Reset',
            'Zend_Form_Element_Submit'
        );

        $value = $element->getValue();
        foreach ($_buttonTypes as $type) {
            if ($element instanceof $type) {
                $value = $element->getLabel();
            }
        }

        $render = '';
        
        if ($element->readOnly) {
            $element->setAttrib('disabled', 'disabled');
        }
        
        if ($element instanceof Zend_Form_Element_Textarea && $element->readOnly) {
            // Text areas are rendered differently in read only mode:
            $render = "<div class=\"formValue\">$value</div>";
        } elseif (method_exists($element, 'renderSelf')) {
            // If the element can render itself, then call its renderSelf() function
            $render = $element->renderSelf();
        } else {
            // Otherwise, use the element's view helper to render it
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
                        . '</div><div class="clear"></div>';            
            } else {
                $render = "<form method='{$element->getMethod()}'"
                        . " action='{$element->getAction()}'"
                        . (isset($enctype) ? " enctype=\"$enctype\"" : '')
                        . (isset($id) ? " id=\"$id\"" : '')
                        . '>'
                        . '<div class=\'form\'>'
                        . $content
                        . '</div><div class="clear"></div>'
                        . '</form>';
            }
        } else {
            throw new Fisma_Exception("The element to be rendered is an unknown"
                                    . " class: "
                                    . get_class($element));
        }
        
        return $render;
    }
}
