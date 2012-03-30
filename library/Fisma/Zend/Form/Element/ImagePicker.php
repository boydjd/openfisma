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
 * A form element that displays a picture and lets the user select other pictures.
 *
 * @author     Mark E. Haase <mhaase@endeavorystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma_Zend_Form
 * @subpackage Fisma_Zend_Form_Element
 */
class Fisma_Zend_Form_Element_ImagePicker extends Zend_Form_Element
{
    /**
     * A value that represents the selected image.
     *
     * The value of this variable must exist as a key in $_imageUrls.
     *
     * @var int A numeric identifier for the selected image
     */
    private $_selectedImage;

    /**
     * A dictionary of URLs.
     *
     * The dictionary keys are numeric identifiers for images and the dictionary values are URLs where those
     * images can be found.
     *
     * @var array
     */
    private $_imageUrls = array();

    /**
     * If set, the widget will display a button for managing the images that will link to this URL.
     *
     * @var string
     */
    private $_imageManagementUrl;

    /**
     * If set, the widget will display a button for managing the images that will link to this URL.
     *
     * @var string
     */
    private $_uploadUrl;

    /**
     * Render the form element
     *
     * @param Zend_View_Interface $view Not used but required because of parent's render() signature
     * @return string The rendered element
     */
    public function render(Zend_View_Interface $view = null)
    {
        $view = (isset($view)) ? $view : new Fisma_Zend_View();

        $view->setScriptPath(Fisma::getPath('formViews'))
             ->setEncoding('utf-8');

        $view->defaultImageId = $this->getValue();
        $view->imageElementId = $this->getName() . '_image';
        $view->imageManagementUrl = $this->_imageManagementUrl;
        $view->imageUrls = $this->_imageUrls;
        $view->label = $this->getLabel();
        $view->name = $this->getName();
        $view->required = $this->isRequired();
        $view->tableId = $this->getName() . '_td';
        $view->uploadUrl = $this->_uploadUrl;

        return $view->render('image-picker.phtml');
    }

    /**
     * Set the images for this image picker.
     *
     * @param array $imageUrls A dictionary where keys are image identifiers and the values are image URLs.
     * @return Fisma_Zend_Form_Element_ImagePicker Fluent Interface
     */
    public function setImageUrls($imageUrls)
    {
        $this->_imageUrls = $imageUrls;

        return $this;
    }

    /**
     * Set the image management URL for this widget.
     *
     * The widget will display a button that links to this URL. This should only be set
     * if the user actually has access to this URL.
     *
     * @var string $url
     * @return Fisma_Zend_Form_Element_ImagePicker Fluent Interface
     */
    public function setImageManagementUrl($url)
    {
        $this->_imageManagementUrl = $url;

        return $this;
    }

    /**
     * Set the URL for the controller action that will accept image uploads for this image picker.
     *
     * @see  ImagePicker.js:_uploadUrl
     *
     * @var string $url
     * @return Fisma_Zend_Form_Element_ImagePicker Fluent Interface
     */
    public function setImageUploadUrl($url)
    {
        $this->_uploadUrl = $url;

        return $this;
    }
}
