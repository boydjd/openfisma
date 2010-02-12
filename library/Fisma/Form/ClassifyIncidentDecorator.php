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
class Fisma_Form_ClassifyIncidentDecorator extends Fisma_Form_FismaDecorator
{

    /**
     * Decorates the specified content with HTML table markup
     *
     * @return The element rendered in HTML.
     */
    public function render($content) 
    {
        $element = $this->getElement();
        
        if ($element instanceof Zend_Form_Element) {
            if (in_array($element->getName(), array('comment'))) {
                $render = '<tr><td colspan="2" style="text-align: left;"><br />'
                .   $this->buildLabel()
                . '<br />'
                .   $this->buildInput()
                . '</td></td>';
            } elseif (in_array($element->getName(), array('Close'))) {
                $render = "<tr><td colspan='2' style='text-align: left;'><br />".$this->buildInput()."</td></tr>";
            } elseif (in_array($element->getName(), array('Open'))) {
                $render = "<tr><td colspan='2' style='text-align: left;'><br />".$this->buildInput();
            } elseif (in_array($element->getName(), array('Reject'))) {
                $render = $this->buildInput()."</td></tr>";
            } elseif (in_array($element->getName(), array('pii','oig','classification'))) {
                $render = '<tr><td><br />'
                . $this->buildLabel()
                . '</td><td><br />'
                . $this->buildInput(). $content
                . '</td></tr>';
            } elseif (in_array($element->getName(), array('id','step_id'))) {
                $render = $this->buildInput();
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
            throw new Exception_General("The element to be rendered is unknown"
                    . " class: "
                    . get_class($element));
        }
        return $render;
    }
}

