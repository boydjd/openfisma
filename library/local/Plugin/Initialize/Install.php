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
 * @author    Jim Chen <xhorse@users.sourceforge.net>
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 * @package   Local_Plugin_Initialize
 */

/**
 * The base class for all exceptions in OpenFISMA.
 *
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 * @package   Local_Plugin_Initialize
 */
class Plugin_Initialize_Install extends Plugin_Initialize_Webapp
{

    /**
     * @todo english
     * Set database resource
     */
    public function initDb()
    {//overload the parent initDb doing nothing here
    }

    /**
     * @todo english
     * Initialize Zend_Controller's Router Object
     */
    public function initRouters()
    {
        $router = $this->_front->getRouter();
        // If the application has not been installed yet, then define the route so
        // that only the installController can be invoked. This forces the user to
        // complete installation before using the application.
        $route['install'] = new Zend_Controller_Router_Route_Regex (
                                    '([^/]*)/?(.*)$',
                                    array('controller' => 'install'),
                                    array('action' => 2),
                                    'install/%2$s'
                                );
        $router->addRoute('default', $route['install']);
    }
}
