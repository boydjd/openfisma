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
    
    function addCheckbox($name, $label) {
        $this->_checkboxes[] = array('name' => $name, 'label' => $label);
    }
    
    function render() {
        $render = '';
        $columnCount = 3;
        $systemCount = count($this->_checkboxes);
        $rowCount = ceil($systemCount / $columnCount);

        /**
         * @todo Remove hardcoded string "Systems" and replace with
         * a configurable string.
         */
        $render .= "<tr class='fisma_checkboxes'>"
                 . "<td colspan='$columnCount'>Systems</td></tr>";
        for ($currentRow = 0; $currentRow < $rowCount; $currentRow++) {
            $render .= "<tr class='fisma_checkboxes'>";
            for ($currentColumn = 0;
                 $currentColumn < $columnCount;
                 $currentColumn++) {
                $render .= "<td>";
                $currentIndex = $currentColumn * $rowCount + $currentRow;
                if ($currentIndex < $systemCount) {
                    $checkbox = $this->_checkboxes[$currentIndex];
                    $render .= "<input type='checkbox'
                                name='{$checkbox['name']}'
                                value='{$checkbox['name']}'>
                                {$checkbox['label']}\n";
                }
                $render .= "&nbsp;</td>";
            }
            $render .= "</tr>\n";
        }
        $render .= "<tr><td colspan='$columnCount'>"
                . "<input type='button' name='select_none' value='Select None'>"
                . "&nbsp;"
                . "<input type='button' name='select_all' value='Select All'>";
        
        return $render;
    }
}
