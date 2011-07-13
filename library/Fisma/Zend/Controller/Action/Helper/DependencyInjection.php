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
 * Fisma_Zend_Controller_Action_Helper_DependencyInjection 
 * 
 * @uses Zend_Controller_Action_Helper_Abstract
 * @package Fisma_Zend_Controller_Action_Helper 
 * @copyright (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @author Josh Boyd <joshua.boyd@endeavorsystems.com> 
 * @author Loïc Frering <loic.frering@gmail.com>
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Fisma_Zend_Controller_Action_Helper_DependencyInjection extends Zend_Controller_Action_Helper_Abstract
{
    /**
     *
     * @var sfServiceContainer
     */
    protected $_container;

    /**
     * direct 
     * 
     * @param mixed $name 
     * @return void
     */
    public function direct($name)
    {
        if ($this->_container->hasService($name)) {
            return $this->_container->getService($name);
        } elseif ($this->_container->hasParameter($name)) {
            return $this->_container->getParameter($name);
        }
        return null;
    }

    /**
     * getContainer 
     * 
     * @return void
     */
    public function getContainer() 
    {
        return $this->_container;
    }
}
