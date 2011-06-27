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
 * apply the help document for different tips
 *
 * @author     Woody Li <woody712@users.sourceforge.net>
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Controller
 */
class HelpController extends Zend_Controller_Action
{
    /**
     * Apply the help document for different tips
     * 
     * Get the parameter from request and decide which template will be used. If the template is not existed 
     * use a default template.
     * 
     * @return void
     */
    public function helpAction()
    {
        $requestModule = $this->_request->getModuleName();
        $module = $this->_request->getParam('module');
        $this->_helper->layout->setLayout('ajax');
        $this->_helper->viewRenderer->setNoRender();
        $template = $this->getViewScript($module);

        if (is_file(Fisma::getPath('application') . '/modules/' . $requestModule . '/views/scripts/' . $template)) {
            $this->render($module);
        } else {
            $this->render('notFound');
        }
    }

    public function installAction()
    {
    }
}
