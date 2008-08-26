<?
/**
 * Copyright (c) 2008 Endeavor Systems, Inc.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @author    Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 * @version   $Id:$
 */

require_once 'Zend/Form/Element.php';

/**
 * Renders multiple checkboxes in column format.
 *
 * @package   Form
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 */
class Form_CheckboxMatrix extends Zend_Form_Element {
    protected $_checkboxes = array();
    protected $_defaults = array();
    
    /**
     * addCheckbox() - Add a checkbox to this matrix.
     *
     * @param string $name The name of the checkbox (this will be returned in
     * the form)
     * @param string $label The label that is placed next to the checkbox
     */
    function addCheckbox($name, $label) {
        $this->_checkboxes[] = array('name' => $name, 'label' => $label);
    }
    
    /**
     * setValue() - Sets the default value for the checkbox matrix.
     *
     * @param array $value
     */
    function setValue($value) {
        $this->_defaults = $value;
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
    function isValid($dataItem) {
        $this->_defaults = $dataItem;
        return true;
    }

    /**
     * render() - Renders the checkbox matrix into a table.
     *
     * @return string The rendered matrix
     */
    function render() {
        $render = '';
        // @todo ideally this would be configurable in the constructor
        $columnCount = 3;
        $systemCount = count($this->_checkboxes);
        $rowCount = ceil($systemCount / $columnCount);
        
        // These HTML attributes are the same for all checkboxes
        $disabled = $this->getAttrib('disabled') == 'disabled'
                    ? ' disabled=\'disabled\'' : '';
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
                             . "$class$checked$disabled>"
                             . "{$checkbox['label']}\n";
                }
                $render .= "&nbsp;</td>";
            }
            $render .= "</tr>\n";
        }
        // These buttons require some javascript in the page to do their magic.
        // @todo put the javascript here instead of in the view directly
        $render .= "<tr><td colspan='$columnCount'>"
                 . "<input type='button' name='select_none'"
                 . " value='Select None'>&nbsp;"
                 . "<input type='button' name='select_all' value='Select All'>";
        
        return $render;
    }
}
