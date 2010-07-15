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

require_once 'Zend/View/Helper/Abstract.php';

/**
 * Helper for generating HTML page title.
 *
 * @author     Jackson Yang <yangjianshan@users.sourceforge.net>
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    View_Helper
 * @version    $Id$
 */
class View_Helper_PageTitle extends Zend_View_Helper_Abstract
{
    /**
     * Generate HTML page title based on system, controller and action name.
     *
     * @return string The assembled HTML page title of current controller and view.
     */
    public function pageTitle()
    {
        $front   = Zend_Controller_Front::getInstance();
        $request  = $front->getRequest();

        $systemName = Fisma::configuration()->getConfig('system_name');
        $controllerName = $request->getControllerName();
        $actionName = $request->getActionName();

        // try best to correct some cases that do not make any sense.
        if (strcasecmp('index', $actionName) == 0) {
            $actionName = '';
        }
        if (strcasecmp('auth', $controllerName) == 0) {
            $controllerName = '';
        }
        if (strcasecmp('remediation', $controllerName) == 0) {
            $controllerName = 'finding';
        }
        if (strpos($controllerName, '-')) {
            $controllerName = implode('', array_map('ucfirst', explode('-', $controllerName)));
        }
        if (strpos($actionName, '-')) {
            $actionName = implode('', array_map('ucfirst', explode('-', $actionName)));
        }

        $pageTitle = $systemName;
        $words = $this->_splitCamelCasedString($actionName . ucfirst($controllerName));
        if (count($words) > 0) {
            $pageTitle = $pageTitle . ' - ' .trim(implode(' ', $words));
        }

        return $pageTitle;
    }
    
    /**
     * To split camelcased string into word array.
     * 
     * @param string $camelcasedString The camelcased string to be splited
     * @return string The array incluing each of splited word
     */
    protected function _splitCamelCasedString($camelcasedString)
    {
        preg_match_all('/([A-Z][a-z]+)/', ucfirst($camelcasedString), $matches);
        return $matches[0];
    }
}
