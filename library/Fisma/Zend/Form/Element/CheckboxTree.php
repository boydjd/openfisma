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
 * Renders multiple checkboxes in column format.
 *
 * @author     Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Zend_Form
 * @version    $Id$ 
 */
class Fisma_Zend_Form_Element_CheckboxTree extends Zend_Form_Element
{
    /**
     * A array container which keeps all available checkboxes in tree
     * 
     * @var array
     */
    protected $_checkboxes = array();
    
    /**
     * A array container which holds values of all checked chechboxes in tree
     * 
     * @var array
     */
    protected $_defaults = array();
    
    /**
     * Add a checkbox to this checkbox tree.
     * 
     * @param string $name The name of the checkbox (this will be returned in the form)
     * @param string $label The label that is placed next to the checkbox
     * @return void
     */
    function addCheckbox($name, $label, $level, $group = NULL) 
    {
        $this->_checkboxes[] = array('name' => $name, 'label' => $label, 'level'=>$level, 'group' => $group);
    }
    
    /**
     * Sets the default value for the checkbox tree.
     *
     * @param array $value The specifed value of checkbox to be checked by default in tree 
     * @return void
     */
    function setValue($value) 
    {
        $this->_defaults = (array)$value;
    }

    /**
     * Gets the current value for the checkbox matrix. This is only
     * populated after isValid is called(). I believe that is the ZF convention.
     * 
     * @return array The checked checkboxes
     */
    function getValue() 
    {
        return $this->_defaults;
    }

    /**
     * A stub function which always returns true. The main reason
     * for overriding here is to capture the data which is being validated.
     * 
     * @return boolean Always returns true
     */
    function isValid($value, $context=null) 
    {
        $this->setValue($value);
        return true;
    }
    
    /**
     * Renders the checkbox tree into a table.
     * 
     * @param Zend_View_Interface $view Provided for compatibility
     * @return string The rendered checkbox tree in HTML
     */
    function render(Zend_View_Interface $view = null) 
    {
        $render = '';
        $render .= "<span id=\"{$this->getName()}\">";

        // Setup the tooltip
        $tooltipHtml = '<p>Checking a system or organization will automatically select all of the nested'
                     . ' systems and organizations within it. Clicking the same box again will deselect'
                     . ' all of the nested items.</p><p><i>Hold down the Option or Alt key while clicking'
                     . ' in order to select a single checkbox.</i></p>';
        $tooltip = new Fisma_Yui_Tooltip("{$this->getName()}checkboxMatrix", 
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
            $group = $this->getView()->escape($checkbox['group']);
            $name = $this->getView()->escape($checkbox['name']);
            $render .= "<input type='checkbox'"
                     . ' id="' . $groupName . '[' . $group . '][' . $name . ']"'
                     . ' name="' . $groupName . '[' . $group . '][]"'
                     . ' value="' . $name . '"'
                     . ' onclick="Fisma.CheckboxTree.handleClick(this, event);"'
                     . ' nestedLevel="' . $checkbox['level'] . '"'
                     . $class . $checked . $disabled . '>&nbsp;'
                     . '<label for="' . $groupName . '[' . $group . '][' . $name . ']">'
                     . $this->getView()->escape($checkbox['label'])
                     . '</label>&nbsp;</li>';
        }
        $render .= "</ul></td></tr>\n";

        $render .= "<tr><td>"
                 . '<script type="text/javascript" src="/javascripts/selectallselectnone.js"></script>'
                 . "</td></tr>";

        $render .= "</span>";

        return $render;
    }
}
