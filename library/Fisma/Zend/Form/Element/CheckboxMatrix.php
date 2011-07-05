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
 */
class Fisma_Zend_Form_Element_CheckboxMatrix extends Zend_Form_Element
{
    /**
     * Checkbox matrix container which holds all available checkboxes in maxtrix
     * 
     * @var array
     */
    protected $_checkboxes = array();
    
    /**
     * Default value array which indicates which of them checked
     * 
     * @var array
     */
    protected $_defaults = array();
    
    /**
     * Add a checkbox to this matrix.
     *
     * @param string $name The name of the checkbox (this will be returned in the form)
     * @param string $label The label that is placed next to the checkbox
     * @return void
     */
    function addCheckbox($name, $label) 
    {
        $this->_checkboxes[] = array('name' => $name, 'label' => $label);
    }
    
    /**
     * Sets the default value for the checkbox matrix.
     *
     * @param array $value The specified value to be checked by default
     * @return void
     */
    function setValue($value) 
    {
        $this->_defaults = $value;
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
    function isValid($dataItem) 
    {
        $this->_defaults = $dataItem;
        return true;
    }

    /**
     * Renders the checkbox matrix into a table.
     *
     * @return string The rendered matrix in HTML
     */
    function render() 
    {
        $render = '';
        // @todo ideally this would be configurable in the constructor
        $columnCount = 4;
        $systemCount = count($this->_checkboxes);
        $rowCount = ceil($systemCount / $columnCount);
        
        // These HTML attributes are the same for all checkboxes
        $disabled = '';
        if ($this->readOnly) {
            $disabled = ' disabled=\'disabled\'';
        }
        $class = $this->getAttrib('class') != ''
                 ? ' class=\''.$this->getAttrib('class').'\'' : '';

        // Render the checkbox matrix as a table, filling out the columns
        // top to bottom then left to right
        $render .= "<tr class='fisma_checkboxes'>"
                 . "<td colspan='$columnCount'>"
                 . ucfirst($this->getName())
                 . "</td></tr>";
        for ($currentRow = 0; $currentRow < $rowCount; $currentRow++) {
            $render .= "<tr class='fisma_checkboxes'>";
            for ($currentColumn = 0;
                 $currentColumn < $columnCount;
                 $currentColumn++) {
                $render .= "<td>";
                $currentIndex = $currentColumn * $rowCount + $currentRow;
                if ($currentIndex < $systemCount) {
                    $checkbox = $this->_checkboxes[$currentIndex];
                    $checked = in_array($checkbox['name'], $this->_defaults)
                               ? ' checked=\'checked\'' : '';
                    $render .= "<input type='checkbox'"
                             . " name='".$this->getName()."[]'"
                             . " value='{$checkbox['name']}'"
                             . "$class$checked$disabled>&nbsp;"
                             . "{$checkbox['label']}\n";
                }
                $render .= "&nbsp;</td>";
            }
            $render .= "</tr>\n";
        }

        $selectAllButton = new Fisma_Yui_Form_Button(
            'SelectAll',
            array(
                'value' => 'Select All',
                'onClickFunction' => 'selectAllUnsafe'
            )
        );

        $selectNoneButton = new Fisma_Yui_Form_Button(
            'SelectNone',
            array(
                'value' => 'Select None',
                'onClickFunction' => 'selectNoneUnsafe'
            )
        );
        
        $render .= "<tr><td colspan='$columnCount'>"
                 . '<script type="text/javascript" src="/javascripts/selectallselectnone.js"></script>'
                 . "$selectAllButton $selectNoneButton</td></tr>";
        
        return $render;
    }
}
