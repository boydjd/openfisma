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
 * Helper to facilitate sending a file to the client from an action.
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
     * @param bool $inline If true, set disposition to inline. If false, set disposition to attachment.
     * @param bool $cache If true, set far-future caching. If false, set no caching.
     * @return void
     */
    public function downloadAttachment($hash, $filename, $inline = false, $cache = false)
    {
        $fm = Zend_Registry::get('fileManager');
        $controller = $this->getActionController();
        $response = $controller->getResponse();

        $controller->getHelper('layout')->disableLayout(true);
        $controller->getHelper('viewRenderer')->setNoRender();

        $mimeType = $fm->getMimeType($hash);
        $fileSize = $fm->getFileSize($hash);
        $response->setHeader('Content-Type', $mimeType, true);
        $response->setHeader('Content-Length', $fileSize, true);

        if ($inline) {
            $response->setHeader('Content-Disposition', 'inline', true);
        } else {
            $response->setHeader('Content-Disposition', 'attachment; filename="' . urlencode($filename) . '"', true);
        }

        if ($cache) {
            // Cache for 1 year
            $response->setHeader('Cache-Control', "max-age=31556926, private", true)
                     ->setHeader("Last-Modified", gmdate("D, d M Y H:i:s", time() - 31556926) . " GMT", true)
                     ->setHeader("Expires", gmdate("D, d M Y H:i:s", time() + 31556926) . " GMT", true);
        } else {
            $response->setHeader('Expires', 0, true);
            $response->setHeader('Cache-Control', 'no-cache', true);
            $response->setHeader('Pragma', 'none', true);
        }

        $response->sendHeaders();

        $fm->stream($hash);
    }

    /**
     * Perform helper when called as $this->_helper->downloadAttachment() from an action controller
     *
     * @param string $hash
     * @param string $filename
     * @param bool $inline If true, set disposition to inline. If false, set disposition to attachment.
     * @param bool $cache If true, set far-future caching. If false, set no caching.
     */
    public function direct($hash, $filename, $inline = false, $cache = false)
    {
        $this->downloadAttachment($hash, $filename, $inline, $cache);
    }
}
