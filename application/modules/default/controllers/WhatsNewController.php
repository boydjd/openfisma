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
 * For the what's new controller
 * 
 * @author     Mark Ma <mark.ma@reyosoft.com>
 * @copyright  (c) Endeavor Systems, Inc. 2012 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Controllers
 * @subpackage SUBPACKAGE
 */
class WhatsNewController extends Fisma_Zend_Controller_Action_Security
{
    /**
     * Display whats new content
     *
     * @GETAllowed
     */
    public function indexAction()
    {
        $this->_helper->layout->setLayout('whats-new');
        $versions = Zend_Controller_Front::getInstance()->getParam('bootstrap')->getOption('versions');
        $configFile = realpath(Fisma::getPath('config')) . '/whatsnew/' 
                      . substr( $versions['application'], 0, -2) . '/whatsnew.yml';
        
        if (!file_exists($configFile)) {
            throw new Fisma_Zend_Exception('There is no configure file: ' . $configFile);
        }

        $contents = Doctrine_Parser_YamlSf::load($configFile);

        if (!is_array($contents) || count($contents) <= 0) {
            throw new Fisma_Zend_Exception('There is no content in: ' . $configFile);
        }

        $this->view->systemName = Fisma::configuration()->getConfig('system_name'); 
        $this->view->contents = $contents;
    }
}
