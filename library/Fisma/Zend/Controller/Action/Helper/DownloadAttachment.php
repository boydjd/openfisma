<?php
/**
 * Copyright (c) 2011 Endeavor Systems, Inc.
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
 * Translate the criteria to a string which can be used in an URL
 * OpenFISMA.
 *
 * @author     Andrew Reeves <andrew.reeves@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Zend_Controller
 */
class Fisma_Zend_Controller_Action_Helper_DownloadAttachment extends Zend_Controller_Action_Helper_Abstract
{
    /**
     * Set up the response to send the file and stream it to the client.
     *
     * @param string $hash
     * @param string $filename
     * @return void
     */
    public function downloadAttachment($hash, $filename)
    {
        $fm = Zend_Registry::get('fileManager'); 
        $this->getControllerAction()->getHelper('layout')->disableLayout(true);
        $this->getControllerAction()->getHelper('viewRenderer')->setNoRender();

        $mimeType = $fm->getMimeType($upload->fileHash);
        // @TODO Make these Zend Response class calls.
        header("Content-Type: $mimeType", true);
        header('Content-Disposition: attachment; filename="' . urlencode($upload->fileName) . '"', true);
        header('Expires: 0', true);
        header('Cache-Control: none', true);
        header('Pragma: none', true);
        $fileSize = $fm->getFileSize($upload->fileHash);
        header("Content-Length: $fileSize", true);

        $fm->stream($upload->fileHash);
    }
    
    /**
     * Perform helper when called as $this->_helper->downloadAttachment() from an action controller
     * 
     * @param string $hash
     * @param string $filename
     */
    public function direct($hash, $filename)
    {
        $this->downloadAttachment($hash, $filename);
    }
}
