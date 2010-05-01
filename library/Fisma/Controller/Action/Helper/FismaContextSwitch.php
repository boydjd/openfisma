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
 * @subpackage Fisma_Controller
 * @version    $Id $
 */
class Fisma_Controller_Action_Helper_FismaContextSwitch extends Zend_Controller_Action_Helper_ContextSwitch
{
    /**
     * Add extra initialization steps when this helper is used instead of the Zend version.
     *
     * @return void
     */
    public function init()
    {
        session_cache_limiter(false);
        parent::init();

        if (!$this->hasContext('pdf')) {
            $this->addContext(
                'pdf',
                array(
                    'suffix' => 'pdf',
                    'headers' => array(
                        'Content-Disposition' => 'attachement;filename="export.pdf"',
                        'Content-Type' => 'application/pdf'
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
                        'Content-type' => 'application/vnd.ms-excel',
                        'Content-Disposition' => 'filename=Fisma_Report.xls'
                    )
                )
            );
        }
    }
}
