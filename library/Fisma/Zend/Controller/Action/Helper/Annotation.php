<?php
/**
 * Copyright (c) 2012 Endeavor Systems, Inc.
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
 * Action helper for performing forced action enforcement 
 *
 * @uses       Zend_Controller_Action_Helper_Abstract
 * @package    Fisma_Zend_Controller_Action_Helper
 * @copyright  (c) Endeavor Systems, Inc. 2012 {@link http://www.endeavorsystems.com}
 * @author     Mark Ma <mark.ma@reyosoft.com>
 * @license    http://www.openfisma.org/content/license GPLv3
 */
class Fisma_Zend_Controller_Action_Helper_Annotation extends Zend_Controller_Action_Helper_Abstract
{

    /**
     * Return a docblock of an action
     * 
     * @return String contains action docblock 
     */
    protected function _getDocblockForControllerAction()
    {
        $actionName = $this->getRequest()->getActionName();
        $actionName = $this->getFrontController()->getDispatcher()->formatActionName($actionName);
        $reflectionClass = new Zend_Reflection_Class($this->getActionController());
        if (!$reflectionClass->hasMethod($actionName)) {
            return NULL;
        }

        $reflectionMethod = $reflectionClass->getMethod($actionName);
        $docBlock = $reflectionMethod->getDocComment();

        return $docBlock;
    }

}
