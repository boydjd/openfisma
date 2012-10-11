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
 * Upload
 *
 * @author     Duy K. Bui <duy.bui@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Model
 */
class Upload extends BaseUpload
{
    /**
     * Extensions which should not be attached
     *
     * @var array
     */
    private $_extensionsBlackList = array(
        /* CSS        */ 'css',
        /* Executable */ 'app', 'exe', 'com',
        /* HTML       */ 'htm', 'html', 'xhtml',
        /* Java       */ 'class',
        /* Javascript */ 'js',
        /* PHP        */ 'php', 'phtml', 'php3', 'php4', 'php5',
    );

    /**
     * MIME types which should not be attached
     *
     * @var array
     */
     private $_mimeTypeBlackList = array(
         /* CSS        */ 'text/css',
         /* HTML       */ 'text/html', 'application/xhtml+xml',
         /* Javascript */ 'application/x-javascript', 'text/javascript', 'application/ecmascript',
     );

     /**
     * Create an upload from the HTTP Request File info array
     *
     * @param mixed $file The array mapped from HTTP Request File info
     * @return Upload
     */
    public function instantiate($file)
    {
        $this->checkFileBlackList($file);

        $fm = Zend_Registry::get('fileManager');
        $hash = $fm->store($file['tmp_name']);

        $this->fileName = $file['name'];
        $this->fileHash = $hash;
        $this->User = CurrentUser::getInstance();
        if (isset($_SERVER['REMOTE_ADDR'])) {
            $this->uploadIp = $_SERVER['REMOTE_ADDR'];
        } else {
            $this->uploadIp = '127.0.0.1';
        }

        return $this;
    }

    /**
     * Get a URL for this file's icon
     *
     * The icon is derived from the file extension
     *
     * @return string
     */
    public function getIconUrl()
    {
        $pi = pathinfo($this->fileName);
        $extension = (!empty($pi['extension'])) ? strtolower($pi['extension']) : '';
        $imagePath = Fisma::getPath('image');

        if (file_exists("$imagePath/mimetypes/$extension.png")) {
            return "/images/mimetypes/$extension.png";
        } else {
            return "/images/mimetypes/unknown.png";
        }
    }

    /**
     * Get the size of the file
     *
     * @return int
     */
    public function getFileSize()
    {
        return Zend_Registry::get('fileManager')->getFileSize($this->fileHash);
    }

    /**
     * Get the size of the file and display it in human-friendly form
     *
     * E.g. 1.2M or 4.7K
     *
     * @return string
     */
    public function getDisplayFileSize()
    {
        $fileSize = $this->getFileSize();

        if ($fileSize < 1024) {
            $size = $fileSize;
            $units = 'bytes';
        } elseif ($fileSize < 1048576) {
            $size = sprintf("%.1f", $fileSize / 1024);
            $units = 'KB';
        } elseif ($fileSize < 1073741824) {
            $size = sprintf("%.1f", $fileSize / 1048576);
            $units = 'MB';
        } else {
            $size = sprintf("%.1f", $fileSize / 1073741824);
            $units = 'GB';
        }

        return "$size $units";
    }

    /**
     * Check the specified file against the blacklist to see if it is disallowed
     *
     * @param array $file File information in array format as specified in the $_FILES super-global
     * @throw Fisma_Zend_Exception_User If the user has specified a file type which is black listed
     */
    public function checkFileBlackList($file)
    {
        // Check file extension
        $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);

        if (in_array($fileExtension, $this->_extensionsBlackList)) {
            throw new Fisma_Zend_Exception_User("This file type (.$fileExtension) is not allowed.");
        }

        // Check mime type
        if (in_array($file['type'], $this->_mimeTypeBlackList)) {
            throw new Fisma_Zend_Exception_User("This file type ({$file['type']}) is not allowed.");
        }
    }
}
