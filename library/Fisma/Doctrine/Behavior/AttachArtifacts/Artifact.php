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
 * The base class for all generated artifact classes
 * 
 * @author     Mark E. Haase
 * @copyright  (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Doctrine_Behavior_AttachArtifacts
 */
class Fisma_Doctrine_Behavior_AttachArtifacts_Artifact extends Fisma_Doctrine_Record
{
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
    public function getFileSize()
    {
        if ($this->fileSize < 1024) {
            $size = $this->fileSize;
            $units = 'bytes';
        } elseif ($this->fileSize < 1048576) {
            $size = sprintf("%.1f", $this->fileSize / 1024);
            $units = 'KB';
        } elseif ($this->fileSize < 1073741824) {
            $size = sprintf("%.1f", $this->fileSize / 1048576);
            $units = 'MB';
        } else {
            $size = sprintf("%.1f", $this->fileSize / 1073741824);
            $units = 'GB';
        }

        return "$size $units";
    }

    /**
     * Send a file to the user
     * 
     * This method fulfills an HTTP request by setting the appropriate headers and then streaming the binary data
     * to the user's browser
     * 
     * @return void
     */
    public function send()
    {
        $path = $this->getStoragePath() . $this->fileName;

        header("Content-Type: $this->mimeType", true);
        header('Content-Disposition: attachment; filename="' . basename($this->fileName) . '"', true);
        header('Expires: 0', true);
        header('Cache-Control: none', true);
        header('Pragma: none', true);
        header("Content-Length: $this->fileSize", true);
        
        readfile($path);
    }
    
    /**
     * Returns the path to the directory where this artifact is stored
     * 
     * The path is data/uploads/<table_name>/<id>
     * 
     * This will create any missing directories before returning the path
     * 
     * @return string
     */
    public function getStoragePath()
    {
        $path = Fisma::getPath('uploads')
              . '/'
              . (Doctrine_Inflector::tableize(get_class($this)))
              . '/'
              . $this->objectId
              . '/';

        // Try to create directory (and parents) if it does not exist already
        if (!is_dir($path)) {
            $mkdirResult = mkdir($path, 0777 & ~umask(), true);
            
            if (!$mkdirResult) {
                throw new Fisma_Zend_Exception("Unable to make artifact path: $path");
            }
        }

        return $path;
    }
}
