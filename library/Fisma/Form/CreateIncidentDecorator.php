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
 * @version   $Id$
 * @package   Fisma_Form
 */

/**
 * A specfic  decorator that can be used for create incident
 *
 * @package   Fisma_Form
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 * @todo rename this class to "CrudDecorator"
 */
class Fisma_Form_CreateIncidentDecorator extends Fisma_Form_FismaDecorator
{

    /**
     * Decorates the specified content with HTML table markup
     *
     * @return The element rendered in HTML.
     */
    public function render($content) 
    {
        $element = $this->getElement();
 
        // Render the HTML 4.01 strict markup for the form and form elements.
        if ($element instanceof Zend_Form_Element) {
            if (in_array($element->getName(), array('reporter_title','reporter_first_name','reporter_last_name'))) {
                $render = '<td>'
                .   $this->buildLabel()
                . '<br />'
                .   $this->buildInput()
                . '</td>';
            } elseif (in_array($element->getName(), array('reporter_organization', 'reporter_address1', 'reporter_phone', 'reporter_email'))) {
                $render = '<tr><td><br />'
                . $this->buildLabel()
                . '</td><td colspan="2"><br />'
                . $this->buildInput(). $content
                . '</td></tr>';
            } elseif (in_array($element->getName(), array('reporter_address2', 'reporter_fax'))) {
                $render = '<tr><td>'
                . $this->buildLabel()
                . '</td><td colspan="2">'
                . $this->buildInput(). $content
                . '</td></tr>';
            } elseif (in_array($element->getName(), array('reporter_city'))) {
                $render = '<tr><td>'
                . 'City, State Zip</td><td>'
                . $this->buildInput(). $content
                . '</td>';
            } elseif (in_array($element->getName(), array('reporter_state'))) {
                $render = '<td>'
                . $this->buildInput();
            } elseif (in_array($element->getName(), array('reporter_zip'))) {
                $render = $this->buildInput()
                . '</td></tr>';
            } elseif (in_array($element->getName(), array('incident_hour'))) {
                $render = '<tr><td>'
                . $this->buildLabel()
                . '<td>'
                . $this->buildInput()
                . ' : ';
            } elseif (in_array($element->getName(), array('incident_minute', 'incident_ampm'))) {
                $render = $this->buildInput();
            } elseif (in_array($element->getName(), array('incident_tz'))) {
                $render = '&nbsp;&nbsp;&nbsp;&nbsp;'
                . $this->buildInput()
                .   '</td></tr>';
            } elseif (in_array($element->getName(), array('classification'))) {
                $render = '<tr><td><br />'
                . $this->buildLabel()
                . '</td><td><br />'
                . $this->buildInput(). $content
                . '</td></tr>';
            } elseif (in_array($element->getName(), array('additional_info', 'host_additional', 'actions_taken', 'pii_additional', 'pii_shipment_timeline', 'pii_shipment_tracking_numbers'))) {
                $render = '<tr><td colspan="2" style="text-align: left"><br /><br />'
                .   $this->buildLabel()
                .   '<br />'
                .   $this->buildInput()
                .   '</td></tr>';
            } elseif (in_array($element->getName(), array('host_name'))) {
                $render = '<tr><td style="text-align: left"><br />'
                . $this->buildLabel()
                . '<br />'
                . $this->buildInput()
                . '</td>';
            } elseif (in_array($element->getName(), array('host_ip'))) {
                $render = '<td><br />'
                . $this->buildLabel()
                . '<br />'
                . $this->buildInput()
                . '</td></tr>';
            } elseif (in_array($element->getName(), array('host_os'))) {
                $render = '<tr><td style="text-align: left">'
                . $this->buildLabel()
                . '<br />'
                . $this->buildInput()
                . '</td><td>&nbsp;</td></tr>';
            } elseif (in_array($element->getName(), array('pii_involved'))) {
                $render = '<tr><td width="40%">'
                . $this->buildLabel()
                . '</td><td>'
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
            $render = '<div class=\'subform\'><table class=\'fisma_crud\'>'
            . $content
            . '</table></div><br clear="all" />';
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

