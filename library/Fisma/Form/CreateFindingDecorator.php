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
 * A specfic decorator that can be used for create finding
 * 
 * @author     Ryan Yang <ryan@users.sourceforge.net>
 * @copyright  (c) Endeavor Systems, Inc. 2009 (http://www.endeavorsystems.com)
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Form
 * @version    $Id$
 */
class Fisma_Form_CreateFindingDecorator extends Fisma_Form_FismaDecorator
{
    /**
     * Display product detail information on the create finding page
     */
    public function buildProduct()
    {
        $content = '<td colspan="6" width="450px" valign="top">'
                   . '<fieldset style="height:115; border:1px solid #44637A; padding:5">'
                   . ' <legend><b>Asset Information</b></legend>'
                   . '<div id="asset_info"></div>'
                   . '</fieldset>'
                   . '</td>';
        return $content;
    }

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
            if (in_array($element->getName(), array('name', 'ip', 'port', 'searchAsset'))) {
                $render = '<td>'
                . $this->buildLabel()
                . '</td><td>'
                . $this->buildInput()
                . '</td>';
            } elseif ('assetId' == $element->getName()) {
                $render = '<tr><td>'
                . $this->buildLabel()
                . '</td><td>'
                . $this->buildInput() 
                . '</td>'
                . $this->buildProduct()
                . '</tr>';
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
            . '</table></div>';
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
