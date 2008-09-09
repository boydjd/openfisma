<?
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
 * @version   $Id:$
 */

require_once 'Zend/Form.php';
require_once 'Zend/Config/Ini.php';

/**
 * Provides an interface for loading forms that takes care of setting up the
 * common aspects of all forms used in OpenFISMA, such as standard decorators,
 * validators, and filters.
 *
 * @package   Form
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 */
class Form_Manager {
    /**
     * loadForm() - Loads a specified form by looking in the standard forms
     * directory.
     *
     * @param string $formName The name of the form to load. This form should
     * exist inside the forms directory. (Do not include the '.ini' file
     * extension.)
     * @return Zend_Form
     */
    static function loadForm($formName) {
        // Load the form from an .ini file
        $config = new Zend_Config_Ini(FORMS . "/$formName.ini", $formName);
        $form = new Zend_Form($config);

        return $form;
    }

    /**
     * prepareForm() - Adds the standard decorators and filters to the specified
     * form.
     *
     * @param Zend_Form $form
     * @return Zend_Form The modified form
     */
    static function prepareForm($form) {
        $form->setMethod('post');
        
        // Use the FismaDecorator for all Display Groups and Elements
        $form->addPrefixPath('Form', '../apps/forms', 'decorator');
        $form->setDecorators(array(
            'FormElements',
            'FismaDecorator'
        ));

        $form->addDisplayGroupPrefixPath('Form', '../apps/forms', 'decorator');
        $form->setDisplayGroupDecorators(array(
            'FormElements',
            'FismaDecorator'
        ));

        $form->addElementPrefixPath('Form', '../apps/forms', 'decorator');
        $form->setElementDecorators(array('FismaDecorator'));
        
        // By default, all input is trimmed of extraneous white space
        $form->setElementFilters(array('StringTrim'));
        
        return $form;
    }
}
