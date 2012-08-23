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
 * Bulk upload the vulnerability scan files
 *
 * @author     Xue-Wei Tang
 * @copyright  (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Cli
 */

class Fisma_Cli_VmScanBulkUpload extends Fisma_Cli_Abstract
{
    /**
     * Configure the arguments accepted for this CLI program
     *
     * @return array An array containing getopt long syntax
     */
    public function getArgumentsDefinitions()
    {
        return array(
            'networkId|n=i' => "Network ID",
            'OrgSystemId|o=i' => "Organization System ID",
            'filepath|f=s' => "File path",
            'userId|u=i' => "User ID"
        );
    }

    /**
     * Run the backup routine
     */
    protected function _run()
    {
        Zend_Registry::set(
            'fileManager',
            new Fisma_FileManager(Fisma::getPath('fileStorage'), new finfo(FILEINFO_MIME))
        );

        // config vars
        $this->_appRoot = realpath(APPLICATION_PATH . '/../');
        $this->getLog()->info("Application directory is " . $this->_appRoot);

        if (is_null($this->getOption('networkId'))) {
            throw new Fisma_Zend_Exception_User ("Network ID (-n) is required.");
        }
        if (is_null($this->getOption('OrgSystemId'))) {
            throw new Fisma_Zend_Exception_User ("Organization System ID (-o) is required.");
        }
        if (is_null($this->getOption('filepath'))) {
            throw new Fisma_Zend_Exception_User ("File path (-f) is required.");
        }
        if (is_null($this->getOption('userId'))) {
            throw new Fisma_Zend_Exception_User ("User ID (-u) is required.");
        }

        $nid = $this->getOption('networkId');
        $oid = $this->getOption('OrgSystemId');
        $filepath = $this->getOption('filepath');
        $userid = $this->getOption('userId');

        $filebase = pathinfo($filepath, PATHINFO_BASENAME);
        $filename = pathinfo(basename($filepath), PATHINFO_FILENAME);
        $fileext = pathinfo($filepath, PATHINFO_EXTENSION);

        $file = array('networkId' => $nid,
                      'orgSystemId' => $oid,
                      'selectFile' => $filebase,
                      'filepath' => $filepath,
                      'filename' => $filename,
                      'fileext' => $fileext
                     );

        $this->getLog()->info("File: " . print_r($file, true)) ;

        $bulk = new Fisma_Vulnerability_BulkUpload();
        $bulk->process($file, $userid);

        $this->getLog()->info("Bulk upload completed successfully!");
        return true;
    }

}
