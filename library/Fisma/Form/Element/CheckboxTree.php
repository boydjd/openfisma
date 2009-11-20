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
 * Renders multiple checkboxes in column format.
 *
 * @author     Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2009 (http://www.endeavorsystems.com)
 * @license    http://www.openfisma.org/content/license
 * @package    Fisma
 * @subpackage Fisma_Form
 * @version    $Id$ 
 */
class Fisma_Form_Element_CheckboxTree extends Zend_Form_Element
{
    protected $_checkboxes = array();
    protected $_defaults = array();
    
    /**
     * addCheckbox() - Add a checkbox to this matrix.
     *
     * @param string $name The name of the checkbox (this will be returned in
     * the form)
     * @param string $label The label that is placed next to the checkbox
     */
    function addCheckbox($name, $label, $level) {
        $this->_checkboxes[] = array('name' => $name, 'label' => $label, 'level'=>$level);
    }
    
    /**
     * setValue() - Sets the default value for the checkbox matrix.
     *
     * @param array $value
     */
    function setValue($value) {
        $this->_defaults = (array)$value;
    }

    /**
     * getValue() - Gets the current value for the checkbox matrix. This is only
     * populated after isValid is called(). I believe that is the ZF convention.
     *
     * @return array The checked checkboxes
     */
    function getValue() {
        return $this->_defaults;
    }

    /**
     * isValid() - A stub function which always returns true. The main reason
     * for overriding here is to capture the data which is being validated.
     *
     * @return boolean Always returns true
     */
    function isValid($value, $context=null) {
        $this->setValue($value);
        return true;
    }
    
    /**
     * render() - Renders the checkbox matrix into a table.
     *
     * @return string The rendered matrix
     */
    function render(Zend_View_Interface $view = null) {
        $render = '';
        
        // Setup the tooltip
        $tooltipHtml = '<p>Checking a system or organization will automatically select all of the nested'
                     . ' systems and organizations within it. Clicking the same box again will deselect'
                     . ' all of the nested items.</p><p><i>Hold down the Option or Alt key while clicking'
                     . ' in order to select a single checkbox.</i></p>';
        $tooltip = new Fisma_Yui_Tooltip('checkboxMatrix', 
                                         ucfirst($this->getLabel()), 
                                         $tooltipHtml);
        
        // Setup HTML attributes
        $disabled = '';
        if ($this->readOnly) {
            $disabled = ' disabled=\'disabled\'';
        }
        $class = $this->getAttrib('class') != ''
                 ? ' class=\''.$this->getAttrib('class').'\'' : '';

        // Render the checkbox tree as a list with CSS indents based on the nesting level
        $groupName = $this->getName();
        $render .= "<tr class='fisma_checkboxes'>"
                 . "<td style=\"text-align:left\">"
                 . $tooltip
                 . "</td></tr>";
        $render .= "<tr><td><ul class='treelist'>";
        foreach ($this->_checkboxes as $checkbox) {
            $render .= "<li style=\"padding-left:" 
                     . (2*$checkbox['level']) 
                     . "em\">";
            $checked = in_array($checkbox['name'], $this->_defaults) ? ' checked=\'checked\'' : '';
            $render .= "<input type='checkbox'"
                     . " id =\"{$groupName}[{$checkbox['name']}]\""
                     . " name=\"{$groupName}[]\""
                     . " value='{$checkbox['name']}'"
                     . ' onclick=\'YAHOO.fisma.CheckboxTree.handleClick(this, event);\''
                     . " nestedLevel=\"{$checkbox['level']}\""
                     . "$class$checked$disabled>&nbsp;"
                     . "<label for=\"{$groupName}[{$checkbox['name']}]\">{$checkbox['label']}</label>"
                     . "&nbsp;</li>";
        }
        $render .= "</ul></td></tr>\n";

        $selectAllButton = new Fisma_Yui_Form_Button(
            'Select All',
            array(
                'value' => 'Select All',
                'onClickFunction' => 'selectAllUnsafe'
            )
        );

        $selectNoneButton = new Fisma_Yui_Form_Button(
            'Select None',
            array(
                'value' => 'Select None',
                'onClickFunction' => 'selectNoneUnsafe'
            )
        );
        
        $render .= "<tr><td>"
                 . '<script type="text/javascript" src="/javascripts/selectallselectnone.js"></script>'
                 . "$selectAllButton $selectNoneButton</td></tr>";
        
        return $render;
    }
}
