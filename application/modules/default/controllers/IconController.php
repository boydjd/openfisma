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
     *
     * @GETAllowed
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

        $file = $_FILES['imageUpload'];
        if (Fisma_FileManager::getUploadFileError($file)) {
            $response->fail(Fisma_FileManager::getUploadFileError($file));
            return;
        }

        // Create thumbnails
        $thirtyTwoImage = $this->_makeThumbnail($_FILES['imageUpload'], 32, 32);
        $thirtyTwoUpload = $this->_saveThumbnail($thirtyTwoImage, $_FILES['imageUpload']);

        $sixteenImage = $this->_makeThumbnail($_FILES['imageUpload'], 16, 16);
        $sixteenUpload = $this->_saveThumbnail($sixteenImage, $_FILES['imageUpload']);

        // Create the Icon object
        $icon = new Icon;
        $icon->LargeIconFile = $thirtyTwoUpload;
        $icon->SmallIconFile = $sixteenUpload;
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

    /**
     * Create a PNG thumbnail with the specified resolution.
     *
     * @param string $fileArray The PHP $_FILES array for the specified file.
     * @param int $width
     * @param int $height
     * @return Imagick Returns the thumbnail image.
     */
    private function _makeThumbnail($fileArray, $width, $height)
    {
        $thumbnail = new Imagick($fileArray["tmp_name"]);
        $thumbnail->resizeImage($width, $height, imagick::FILTER_LANCZOS, 1);
        $thumbnail->setImageFormat('png');

        return $thumbnail;
    }

    /**
     * Save a thumbnail using the file manager.
     *
     * @param Imagick $image
     * @param array $fileArray The $_FILES array for this image.
     * @return Upload
     */
    private function _saveThumbnail($image, $fileArray)
    {
        $thumbnailPath = tempnam(sys_get_temp_dir(), "image_upload_");

        if ($thumbnailPath === FALSE) {
            throw new Fisma_Zend_Exception("Cannot create a temp file for storing the thumbnail.");
        }

        $image->writeImage($thumbnailPath);
        $fileArray["tmp_name"] = $thumbnailPath;

        $thumbnailUpload = new Upload;
        $thumbnailUpload->instantiate($fileArray);
        $thumbnailUpload->save();

        return $thumbnailUpload;
    }

    /**
     * List all available icons
     *
     * @GETAllowed
     * @return void
     */
    public function manageAction()
    {
        $this->_acl->requirePrivilegeForClass('manage', 'Icon');

        $buttons['upload-image'] = new Fisma_Yui_Form_Button(
            'uploadImageButton',
            array(
                'label' => 'Upload Image',
                'onClickFunction' => 'Fisma.Icon.showPanel',
                'imageSrc' => '/images/up.png'
            )
        );

        $this->view->toolbarButtons = $buttons;

        $query = Doctrine_Query::create()
            ->from('Icon i')
            ->leftJoin('i.SystemTypes st')
            ->leftJoin('i.OrganizationTypes ot');
        $icons = $query->execute();

        $iconRows = array();
        foreach ($icons as $icon) {
            $imageUrl = '/icon/get/id/' . $icon->id;
            $deleteUrl = '/icon/delete/id/' . $icon->id;
            $user = $icon->LargeIconFile->User;
            $inUse = ((count($icon->SystemTypes) + count($icon->OrganizationTypes)) > 0) ? "YES" : "NO";
            $iconRows[] = array(
                'id'                => $icon->id,
                'iconUrl'           => $imageUrl,
                'uploadedBy'        => $this->view->userInfo($user->displayName, $user->id),
                'uploadedAt'        => $icon->LargeIconFile->createdTs,
                'inUse'             => $inUse,
                'delete'            => $deleteUrl
            );
        }

        $iconTable = new Fisma_Yui_DataTable_Local();
        $iconTable->addColumn(new Fisma_Yui_DataTable_Column(
            'ID',
            true,
            null,
            null,
            'id',
            !Fisma::debug(),
            'number'
        ));
        $iconTable->addColumn(new Fisma_Yui_DataTable_Column(
            'Icon',
            false,
            'Fisma.TableFormat.imageControl'
        ));
        $iconTable->addColumn(new Fisma_Yui_DataTable_Column(
            'Uploaded By',
            false,
            'Fisma.TableFormat.formatHtml'
        ));
        $iconTable->addColumn(new Fisma_Yui_DataTable_Column(
            'Uploaded At',
            true
        ));
        $iconTable->addColumn(new Fisma_Yui_DataTable_Column(
            'In Use',
            true,
            'Fisma.TableFormat.yesNo'
        ));
        $iconTable->addColumn(new Fisma_Yui_DataTable_Column(
            'Action',
            false,
            'Fisma.TableFormat.deleteControl'
        ));
        $iconTable->setData($iconRows);

        $this->view->iconTable = $iconTable;
    }

    /**
     * Delete an icon
     *
     * @return void
     */
    public function deleteAction()
    {
        $this->_acl->requirePrivilegeForClass('manage', 'Icon');

        $id = $this->getRequest()->getParam('id');
        $icon = Doctrine_Query::create()
            ->from('Icon i')
            ->leftJoin('i.SystemTypes st')
            ->leftJoin('i.OrganizationTypes ot')
            ->where('i.id = ?', $id)
            ->execute()
            ->getFirst();
        if (!$icon) {
           throw new Fisma_Zend_Exception("No icon found with id ($id).");
        }

        $defaultIcon = Doctrine_Query::create()
            ->from('Icon i')
            ->where('i.id <> ?', $id)
            ->fetchOne();

        if ($defaultIcon) {
            try {
                Doctrine_Manager::connection()->beginTransaction();

                foreach ($icon->SystemTypes as $st) {
                    $st->Icon = $defaultIcon;
                    $st->save();
                }
                foreach ($icon->OrganizationTypes as $ot) {
                    $ot->Icon = $defaultIcon;
                    $ot->save();
                }
                $icon->delete();

                // Commit
                Doctrine_Manager::connection()->commit();
            } catch (Doctrine_Exception $e) {
                // We cannot access the view script from here (for priority messenger), so rethrow after roll-back
                Doctrine_Manager::connection()->rollback();
                throw $e;
            }
            $this->view->priorityMessenger("Icon deleted successfully");
        } else {
            $this->view->priorityMessenger("There must be at least 1 icon.", "warning");
        }

        $this->_redirect('/icon/manage');
    }

    /**
     * Renders the form for uploading icon
     *
     * @GETAllowed
     * @return void
     */
    function uploadFormAction()
    {
        $this->_helper->layout()->disableLayout();

        $form = Fisma_Zend_Form_Manager::loadForm('upload_icon');

        $this->view->form = $form;
    }
    /**
     * Upload one or more image files
     *
     * @GETAllowed
     * @return void
     */
    public function uploadIconAction()
    {
        if (isset($_FILES['imageUpload'])) {
            try {
                for ($i = 0; $i< count($_FILES['imageUpload']['name']); $i++) {
                    $file = array();
                    foreach ($_FILES['imageUpload'] as $key => $value) {
                        $file[$key] = $value[$i];
                    }

                    if (Fisma_FileManager::getUploadFileError($file)) {
                        $message = Fisma_FileManager::getUploadFileError($file);
                        throw new Fisma_Zend_Exception_User($message);
                    }

                    // Create thumbnails
                    $thirtyTwoImage = $this->_makeThumbnail($file, 32, 32);
                    $thirtyTwoUpload = $this->_saveThumbnail($thirtyTwoImage, $file);

                    $sixteenImage = $this->_makeThumbnail($file, 16, 16);
                    $sixteenUpload = $this->_saveThumbnail($sixteenImage, $file);

                    // Create the Icon object
                    $icon = new Icon;
                    $icon->LargeIconFile = $thirtyTwoUpload;
                    $icon->SmallIconFile = $sixteenUpload;
                    $icon->save();
                }

                $this->view->priorityMessenger("Icon created successfully");
            } catch (Exception $e) {
                if ($e instanceof ImagickException) {
                    $this->view->priorityMessenger("Uploading failed: the file format is not supported.", 'warning');
                } else if ($e instanceof Fisma_Zend_Exception_User) {
                    $this->view->priorityMessenger($e->getMessage(), 'warning');
                }
            }
        }

        $this->_redirect("/icon/manage");
    }
}
