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
     * Name to send to the browser in the Content-Disposition header.  Null means to use the contexts' defaults.
     *
     * @var string
     */
    protected $_dispositionFilename = null;

    /**
     * Add extra initialization steps when this helper is used instead of the Zend version.
     *
     * @return void
     */
    public function init()
    {
        parent::init();

        // Fix an IE bug where JSON responses in an iframe are treated as attachments rather than inline.
        $agent = new Zend_Http_UserAgent;
        $device = $agent->getDevice();

        if (stristr($device->getBrowser(), 'explorer')) {
            $this->setContext(
                'json',
                array(
                    'suffix'    => 'json',
                    'headers'   => array('Content-Type' => 'text/html'),
                    'callbacks' => array(
                        'init' => 'initJsonContext',
                        'post' => 'postJsonContext'
                    )
                )
            );
        }

        if (!$this->hasContext('pdf')) {
            $pdfFilename = empty($this->_dispositionFilename) ? 'Report.pdf' : $this->_dispositionFilename;
            $this->addContext(
                'pdf',
                array(
                    'suffix' => 'pdf',
                    'headers' => array(
                        'Content-Disposition' => 'attachment; filename=' . $pdfFilename,
                        'Content-Type' => 'application/pdf'
                    ),
                    'callbacks' => array(
                        self::TRIGGER_INIT => '_disableSessionCacheLimiter'
                    )
                )
            );
        }

        if (!$this->hasContext('xls')) {
            $xlsFilename = empty($this->_dispositionFilename) ? 'Report.xls' : $this->_dispositionFilename;
            $this->addContext(
                'xls',
                array(
                    'suffix' => 'xls',
                    'headers' => array(
                        'Content-Disposition' => 'attachment; filename=' . $xlsFilename,
                        'Content-Type' => 'application/vnd.ms-excel'
                    ),
                    'callbacks' => array(
                        self::TRIGGER_INIT => '_disableSessionCacheLimiter'
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

    /**
     * Function to set the filename passed with content-disposition headers.
     *
     * @param string $filename
     * @return void
     */
    public function setFilename($filename)
    {
        $this->_dispositionFilename = $filename;
        // if current context is set, then the context has been initialized and the header will need to be set manually
        if ($this->_currentContext != null && $filename != null) {
            $this->getResponse()->setHeader('Content-Disposition', 'filename=' . $filename, true);
        }
    }
}
