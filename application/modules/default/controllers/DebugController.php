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
 * Provides several different debugging facilities.
 *
 * @author     Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Controller
 */
class DebugController extends Zend_Controller_Action
{
    /**
     * Prepares actions
     *
     * @return void
     * @throws Fisma_Zend_Exception if Debug mode is not enabled
     */
    public function preDispatch()
    {
        if (!Fisma::debug())
            throw new Fisma_Zend_Exception('Action is only allowed in debug mode.');
    }

    /**
     * Display phpinfo()
     * 
     * @return void
     */
    public function phpinfoAction()
    {
    }

    /**
     * Display error log
     *
     * @return void
     */
    public function errorlogAction()
    {
        $this->_helper->layout()->enableLayout();
        $this->_helper->viewRenderer->setNoRender(false);
        $this->view->errorLog = ($errorLog = @file_get_contents(APPLICATION_PATH . '/../data/logs/error.log')) 
            ? $errorLog : 'The error log does not exist.'; 
    }

    /**
     * Display php log
     *
     * @return void
     */
    public function phplogAction()
    {
        $this->view->log = ($log = @file_get_contents(APPLICATION_PATH . '/../data/logs/php.log'))
            ? $log : 'The php log does not exist';
    }
    
    /**
     * Display APC system cache info
     */
    public function apcCacheAction()
    {
        // Cache type can be 'system' or 'user'. Defaults to 'system'.
        $cacheType = $this->getRequest()->getParam('type', 'system');
        
        switch ($cacheType) {
            case 'system':
                $cacheInfo = apc_cache_info();
                break;
            case 'user':
                $cacheInfo = apc_cache_info('user');
                break;
            default:
                throw new Fisma_Zend_Exception("Invalid cache type: '$cacheType'");
                break;
        }

        // Cache info contains summary data and line item data. Separate these into two view variables for clarity.
        $cacheItems = $cacheInfo['cache_list'];
        unset($cacheInfo['cache_list']);
        
        $this->view->cacheType = ucfirst(htmlspecialchars($cacheType));
        $this->view->cacheSummary = $cacheInfo;
        
        $invalidateCacheButton = new Fisma_Yui_Form_Button_Link(
            'exportPdf',
            array(
                'value' => "Invalidate {$this->view->cacheType} Cache",
                'href' => "/debug/invalidate-apc-cache/type/$cacheType"
            )
        );
        $this->view->invalidateCacheButton = $invalidateCacheButton;

        if (count($cacheItems) > 0) {
            $this->view->cacheItemHeaders = array_keys($cacheItems[0]);
            $this->view->cacheItems = $cacheItems;
        }
    }
    
    /**
     * Invalidate APC cache
     */
    public function invalidateApcCacheAction()
    {
        // Cache type can be 'system' or 'user'
        $cacheType = $this->getRequest()->getParam('type', 'system');
        
        switch ($cacheType) {
            case 'system':
                apc_clear_cache();
                break;
            case 'user':
                apc_clear_cache('user');
                break;
            default:
                throw new Fisma_Zend_Exception("Invalid cache type: '$cacheType'");
                break;
        }
        
        $this->_forward('apc-cache');
    }
}
