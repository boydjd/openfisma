<?php
/**
 * Copyright (c) 2010 Endeavor Systems, Inc.
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
 * @author    Andrew Reeves <andrew.reeves@endeavorsystems.com>
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 * @package   Fisma_Zend_Form
 */

/**
 * An element which represents a phone number.  Includes filtering and validation.
 *
 * @copyright (c) Endeavor Systems, Inc. 2010 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 * @package   Fisma_Zend_Form
 */
class Fisma_Zend_Form_Element_Phone extends Zend_Form_Element_Text
{
    /**
     * Override constructor to include our filters and validators.
     *
     * @param  string|array|Zend_Config $spec
     * @param  array|Zend_Config $options
     * @return void
     * @throws Zend_Form_Exception if no element name after initialization
     */
    public function __construct($spec, $options = null)
    {
        parent::__construct($spec, $options);

        $this->addFilter(new Fisma_Zend_Filter_Phone());
        $this->addValidator(new Fisma_Zend_Validate_Phone());
    }

    /**
     * Render the form element
     *
     * @param Zend_View_Interface $view Not used but required because of parent's render() signature
     * @return string The rendered element
     */
    public function render(Zend_View_Interface $view = null) 
    {
        return parent::render($view);
    }

    /**
     * Cludge to get around Fisma_Zend_Form_Manager clearing the set of filters on the element.
     *
     * Clear the filters
     *
     * @return Fisma_Zend_Form_Element_Phone
     */
    public function clearFilters()
    {
        $this->_filters = array(new Fisma_Zend_Filter_Phone());
        return $this;
    }
}
