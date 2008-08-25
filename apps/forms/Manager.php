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