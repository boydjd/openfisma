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
 * Extends the Zend ContextSwitch action helper to enable calling session_cache_limiter() before
 * start_session() is called.
 *
 * @author     Andrew Reeves <andrew.reeves@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Zend_Controller
 * @version    $Id $
 */
class Fisma_Zend_Controller_Action_Helper_FismaContextSwitch extends Zend_Controller_Action_Helper_ContextSwitch
{
    /**
     * Controller property key to utilize for context switching
     * Override from parent.
     *
     * @var string
     */
    protected $_contextKey = 'fismaContexts';

    /**
     * Add extra initialization steps when this helper is used instead of the Zend version.
     *
     * @return void
     */
    public function init()
    {
        parent::init();

        if (!$this->hasContext('pdf')) {
            $this->addContext(
                'pdf',
                array(
                    'suffix' => 'pdf',
                    'headers' => array(
                        'Content-Disposition' => 'attachment; filename=Report.pdf',
                        'Content-Type' => 'application/pdf'
                    ),
                    'callbacks' => array(
                        self::TRIGGER_POST => '_disableSessionCacheLimiter'
                    )
                )
            );
        }
        
        if (!$this->hasContext('xls')) {
            $this->addContext(
                'xls',
                array(
                    'suffix' => 'xls',
                    'headers' => array(
                        'Content-Disposition' => 'attachment; filename=Report.xls',
                        'Content-Type' => 'application/vnd.ms-excel'
                    ),
                    'callbacks' => array(
                        self::TRIGGER_POST => '_disableSessionCacheLimiter'
                    )
                )
            );
        }
        
        // The base class predefines an XML context that causes problems in IE and needs to be replaced
        $this->removeContext('xml');

        $this->addContext(
            'xml',
            array(
                'suffix' => 'xml',
                'headers' => array(
                    'Content-Type' => 'text/xml'
                )
            )
        );
    }

    /**
     * Callback function for disabling the session cache limiter when within one of the
     * file download contexts.
     *
     * @return void
     */
    protected function _disableSessionCacheLimiter()
    {
        session_cache_limiter(false);
    }
}
