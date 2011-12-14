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
     * Create an upload from the HTTP Request File info array
     * 
     * @param mixed $file The array mapped from HTTP Request File info
     * @return Upload
     */
    public static function create($file)
    {
        $upload = new Upload();
        
        $fm = Zend_Registry::get('fileManager');
        $hash = $fm->store($file['tmp_name']);

        $upload->fileName = $file['name'];
        $upload->fileHash = $hash;
        $upload->userId = CurrentUser::getInstance()->id;
        $upload->uploadIp = $_SERVER['REMOTE_ADDR'];

        $upload->save();
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
     * Get the size of the file and display it in human-friendly form
     * 
     * E.g. 1.2M or 4.7K
     * 
     * @return string
     */
    public function getDisplayFileSize()
    {
        $fm = Zend_Registry::get('fileManager');
        $fileSize = $fm->getFileSize($this->fileHash);

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
}
