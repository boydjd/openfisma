<?php
/**
 * Copyright (c) 2012 Endeavor Systems, Inc.
 *
 * This file is part of OpenFISMA.
 *
 * OpenFISMA is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * OpenFISMA is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with OpenFISMA.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Mark Ma <mark.ma@reyosoft.com>
 * @copyright (c) Endeavor Systems, Inc. 2012 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 * @package   Fisma_Zend_Form
 */

/**
 * An element which upload multiple files.
 *
 * @copyright (c) Endeavor Systems, Inc. 2012 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 * @package   Fisma_Zend_Form
 */
class Fisma_Zend_Form_Element_Upload extends Zend_Form_Element_File
{
    /**
     * Render the form element
     *
     * @param Zend_View_Interface $view Not used but required because of parent's render() signature
     * @return string The rendered element
     */
    public function render(Zend_View_Interface $view = null) 
    {
       
        $maxUploadSize = Fisma::configuration()->getConfig('max_file_upload_size'); 
        $maxFileSize = Fisma_String::convertFilesizeToInteger($maxUploadSize);

        $render = '';
        $render .= '<fieldset id="' . $this->getName() . '_upload_file_list" class="uploadFileList">';
        $render .= '<legend>Select File(s):</legend>';
        $render .= "<input type=\"hidden\" name=\"MAX_FILE_SIZE\" value=\"$maxFileSize\"/>";
 
        $render .= '<input type="file" name="' . $this->getName() . '[]" multiple/>';
        $render .= '</fieldset>';

        $render .= '<p> <button class="ie7-only ie8-only ie9-only" id="add-another-file-button">';
        $render .= 'Add another file</button>';
        $render .= '<input type="submit" name="upload_' . $this->getName() .'" value="Upload"/></p>';
        $render .= '<ul> <li>Each file must be <b>under ' 
                   . substr($maxUploadSize, 0, -1) . ' megabytes</b> in size</li>';
        $render .= '<li>Please ensure no <b>Personally Identifiable Information</b> is included (eg, SSN, DOB)</li>';
        $render .= '</ul>';
 
        return $render;

    }
}
