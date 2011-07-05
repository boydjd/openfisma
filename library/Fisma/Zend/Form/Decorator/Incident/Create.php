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
 * @author    Nahtan Harris <nathan.harris@endeavorsystems.org>
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 * @package   Fisma_Zend_Form
 */

/**
 * A specfic  decorator that can be used for create incident
 *
 * @package   Fisma_Zend_Form
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 */
class Fisma_Zend_Form_Decorator_Incident_Create extends Fisma_Zend_Form_Decorator
{

    /**
     * Decorates the specified content with HTML table markup
     *
     * @return The element rendered in HTML.
     */
    public function render($content) 
    {
        $element = $this->getElement();
        
        $content = trim($content);
 
        $render = '';
 
        // Render the HTML 4.01 strict markup for the form and form elements.
        if ($element instanceof Zend_Form_Element_Hidden) {
            $render = $this->buildInput();
        } elseif ($element instanceof Zend_Form_Element) {
            if (in_array($element->getName(), array('reporterTitle','reporterFirstName','reporterLastName'))) {
                $render = '<td>'
                .   $this->buildLabel()
                . '<br />'
                .   $this->buildInput()
                . '</td>';
            } elseif (
                in_array(
                    $element->getName(), array(
                        'reporterOrganization', 
                        'reporterAddress1', 
                        'reporterPhone', 
                        'reporterEmail'
                    )
                )
            ) {
                $render = '<tr><td><br />'
                . $this->buildLabel()
                . '</td><td colspan="2"><br />'
                . $this->buildInput(). $content
                . '</td></tr>';
            } elseif (in_array($element->getName(), array('reporterAddress2', 'reporterFax'))) {
                $render = '<tr><td>'
                . $this->buildLabel()
                . '</td><td colspan="2">'
                . $this->buildInput(). $content
                . '</td></tr>';
            } elseif (in_array($element->getName(), array('reporterCity'))) {
                $render = '<tr><td>'
                . 'City, State Zip</td><td>'
                . $this->buildInput(). $content
                . '</td>';
            } elseif (in_array($element->getName(), array('reporterState'))) {
                $render = '<td>'
                . $this->buildInput();
            } elseif (in_array($element->getName(), array('reporterZip'))) {
                $render = $this->buildInput()
                . '</td></tr>';
            } elseif (in_array($element->getName(), array('incidentHour'))) {
                $render = '<tr><td>'
                . $this->buildLabel()
                . '<td>'
                . $this->buildInput()
                . ' : ';
            } elseif (in_array($element->getName(), array('incidentMinute', 'incidentAmpm'))) {
                $render = $this->buildInput();
            } elseif (in_array($element->getName(), array('incidentTz'))) {
                $render = '&nbsp;&nbsp;&nbsp;&nbsp;'
                . $this->buildInput()
                .   '</td></tr>';
            } elseif (in_array($element->getName(), array('classification'))) {
                $render = '<tr><td><br />'
                . $this->buildLabel()
                . '</td><td><br />'
                . $this->buildInput(). $content
                . '</td></tr>';
            } else {
                $render = '<tr><td>'
                . $this->buildLabel()
                . '</td><td>'
                . $this->buildInput(). $content
                . '</td></tr>';
            }
        } elseif ($element instanceof Zend_Form_DisplayGroup) {
            if (!empty($content)) {
                $render = '<div class=\'subform\'><table class=\'fisma_crud\'>'
                        . $content
                        . '</table></div><br clear="all" />';
            }
        } elseif ($element instanceof Zend_Form) {
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

