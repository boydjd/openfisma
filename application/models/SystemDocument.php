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
 * SystemDocument
 * 
 * @author     Ryan Yang <ryan@users.sourceforge.net>
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Model
 */
class SystemDocument extends BaseSystemDocument
{
    /**
     * Return the physical path to this document
     * 
     * @return string The physical path of the systemDocument
     */
    public function getPath() 
    {
        $path = Fisma::getPath('systemDocument')
              . '/'
              . $this->System->Organization->id
              . '/'
              . $this->fileName;
              
        return $path;
    }
    
    /**
     * Calculate and return size of systemDocument in KB
     * 
     * @return string The file size in KB
     */
    public function getSizeKb()
    {
        return round($this->size / 1024, 0) . " KB";
    }
    
    /**
     * Returns a URL for an icon which represents this document
     * 
     * @return string The file icon URL related to the file extension name
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
}
