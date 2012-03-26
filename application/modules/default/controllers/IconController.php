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
 * This controller handles uploading and downloading icon image files.
 *
 * @author     Mark E. Haase <mhaase@endeavorystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Controllers
 */
class IconController extends Fisma_Zend_Controller_Action_Object
{
    /**
     * The name of the model managed by this object controller.
     *
     * @var string
     */
    protected $_modelName = 'Icon';

    /**
     * Set up contexts
     */
    public function init()
    {
        parent::init();

        $this->_helper->fismaContextSwitch()
                      ->addActionContext('upload', 'json')
                      ->initContext();
    }

    /**
     * Send an icon to the browser.
     *
     * Icons are far-future cached since they are expected not to change.
     */
    public function getAction()
    {
        $id = $this->getRequest()->getParam('id');
        $size = $this->getRequest()->getParam('size', 'large');

        if (empty($id)) {
           throw new Fisma_Zend_Exception("Missing required ID parameter.");
        }

        if (!in_array($size, array('small', 'large'))) {
           throw new Fisma_Zend_Exception("The size parameter must be either 'small' or 'large'.");
        }

        $icon = Doctrine::getTable("Icon")->find($id);

        if (!$icon) {
           throw new Fisma_Zend_Exception("No icon found for id ($id).");
        }

        $imageFile = ($size == 'large') ? $icon->LargeIconFile : $icon->SmallIconFile;

        $this->_helper->downloadAttachment($imageFile->fileHash, $imageFile->fileName, true, true);
    }

    /**
     * Accept an uploaded icon file.
     *
     * Icons are re-encoded to PNG format and sized to two different resolutions: 32x32px and 16x16px. These two
     * resolutions are saved using file manager and then combined into an Icon object.
     *
     * The PNG-encoded 32px image is sent back to the browser in the response along with the ID of the new
     * Icon object.
     */
    public function uploadAction()
    {
        $response = new Fisma_AsyncResponse;
        $this->view->response = $response;

        $rawImagePath = $_FILES["imageUpload"]["tmp_name"];

        // Create the 32px image
        $thirtyTwoImagePath = "{$rawImagePath}_32px.png";

        $thirtyTwoImage = new Imagick($rawImagePath);
        $thirtyTwoImage->resizeImage(32, 32, imagick::FILTER_LANCZOS, 1);
        $thirtyTwoImage->setImageFormat('png');
        $thirtyTwoImage->writeImage($thirtyTwoImagePath);

        $thirtyTwoImageUpload = new Upload;
        $_FILES["imageUpload"]["tmp_name"] = $thirtyTwoImagePath;
        $thirtyTwoImageUpload->instantiate($_FILES["imageUpload"]);
        $thirtyTwoImageUpload->save();

        // Create the 16px image
        $sixteenImagePath = "{$rawImagePath}_16px.png";

        $sixteenImage = new Imagick($rawImagePath);
        $sixteenImage->setFormat('png');
        $sixteenImage->resizeImage(16, 16, imagick::FILTER_LANCZOS, 1);
        $sixteenImage->writeImage($sixteenImagePath);

        $sixteenImageUpload = new Upload;
        $_FILES["imageUpload"]["tmp_name"] = $sixteenImagePath;
        $thirtyTwoImageUpload->instantiate($_FILES["imageUpload"]);
        $sixteenImageUpload->save();

        // Create the Icon object
        $icon = new Icon;
        $icon->LargeIconFile = $thirtyTwoImageUpload;
        $icon->SmallIconFile = $sixteenImageUpload;
        $icon->save();

        // Prepare the response
        $response->identifier = $icon->id;
        $response->imageData = $this->_makeDataUri($thirtyTwoImage);
        $response->imageUrl = "/icon/get/id/{$icon->id}/size/large";
    }

     /**
      * Create a data URL from a PNG image object.
      *
      * @param  Imagick $image
      * @return string
      */
     private function _makeDataUri($image)
     {
         $uri = "data:image/png;base64," .  base64_encode((string)$image);

         return $uri;
     }
}
