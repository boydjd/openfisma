<?php
/**
 * Copyright (c) 2010 Endeavor Systems, Inc.
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
 * A view helper that loads CVSS select forms and sets the default value according to the Vector score.
 * 
 * @uses Zend_View_Helper_Abstract
 * @package View_Helper 
 * @copyright (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @author Christian Smith <christian.smith@endeavorsystems.com> 
 * @license http://www.openfisma.org/content/license GPLv3
 */

class Vm_View_Helper_Cvss extends Zend_View_Helper_Abstract
{
    public function cvss($form, $vector)
    {
        return $this->_getCvssForm($form, $vector);
    }

    private function _getCvssForm($form, $vector)
    {
        
        $form = Fisma_Zend_Form_Manager::loadForm($form);
        $form->setDefaults($vector);

        return Fisma_Zend_Form_Manager::prepareForm($form);
    }
}
