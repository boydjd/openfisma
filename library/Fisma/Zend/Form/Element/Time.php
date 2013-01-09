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
 * @author    Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 * @package   Fisma_Zend_Form
 */

/**
 * An element which represents a time value, including hours, minutes, AM/PM, and timezone
 *
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 * @package   Fisma_Zend_Form
 */
class Fisma_Zend_Form_Element_Time extends Zend_Form_Element
{
    /**
     * Render the form element
     *
     * @param Zend_View_Interface $view Not used but required because of parent's render() signature
     * @return string The rendered element
     */
    public function render(Zend_View_Interface $view = null)
    {
        $view = Zend_Layout::getMvcInstance()->getView();
        return $view->partial(
            'form-element/time.phtml',
            'default',
            array(
                'label' => $this->_label,
                'name' => $this->_name,
                'value' => $this->_value
            )
        );
    }
}
