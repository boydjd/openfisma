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
 * A decorator which override Zend_Form_Decorator_File to set MAX_FILE_SIZE from Fisma configuration 
 * instead of php.ini or .htaccess
 * 
 * @author     Mark Ma <mark.ma@reyosoft.com>
 * @copyright  (c) Endeavor Systems, Inc. 2012 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Zend
 */
class Fisma_Zend_Form_Decorator_File
    extends Zend_Form_Decorator_Abstract
    implements Zend_Form_Decorator_Marker_File_Interface
{
    /**
     * Attributes that should not be passed to helper
     * @var array
     */
    protected $_attribBlacklist = array('helper', 'placement', 'separator', 'value');

    /**
     * Default placement: append
     * @var string
     */
    protected $_placement = 'APPEND';

    /**
     * Get attributes to pass to file helper
     *
     * @return array
     */
    public function getAttribs()
    {
        $attribs   = $this->getOptions();

        if (null !== ($element = $this->getElement())) {
            $attribs = array_merge($attribs, $element->getAttribs());
        }

        foreach ($this->_attribBlacklist as $key) {
            if (array_key_exists($key, $attribs)) {
                unset($attribs[$key]);
            }
        }

        return $attribs;
    }

    /**
     * Render a form file
     *
     * @param  string $content
     * @return string
     */
    public function render($content)
    {
        $element = $this->getElement();
        if (!$element instanceof Zend_Form_Element) {
            return $content;
        }

        $view = $element->getView();
        if (!$view instanceof Zend_View_Interface) {
            return $content;
        }

        $name      = $element->getName();
        $attribs   = $this->getAttribs();
        if (!array_key_exists('id', $attribs)) {
            $attribs['id'] = $name;
        }

        $separator = $this->getSeparator();
        $placement = $this->getPlacement();
        $markup    = array();
        $size      = Fisma_String::convertFilesizeToInteger(Fisma::configuration()->getConfig('max_file_upload_size'));
        if ($size > 0) {
            $element->setMaxFileSize(0);
            $markup[] = $view->formHidden('MAX_FILE_SIZE', $size);
        }

        if (Zend_File_Transfer_Adapter_Http::isApcAvailable()) {
            $markup[] = $view->formHidden(ini_get('apc.rfc1867_name'), uniqid(), array('id' => 'progress_key'));
        } else if (Zend_File_Transfer_Adapter_Http::isUploadProgressAvailable()) {
            $markup[] = $view->formHidden('UPLOAD_IDENTIFIER', uniqid(), array('id' => 'progress_key'));
        }

        if ($element->isArray()) {
            $name .= "[]";
            $count = $element->getMultiFile();
            for ($i = 0; $i < $count; ++$i) {
                $htmlAttribs        = $attribs;
                $htmlAttribs['id'] .= '-' . $i;
                $markup[] = $view->formFile($name, $htmlAttribs);
            }
        } else {
            $markup[] = $view->formFile($name, $attribs);
        }

        $markup = implode($separator, $markup);

        switch ($placement) {
            case self::PREPEND:
                return $markup . $separator . $content;
            case self::APPEND:
            default:
                return $content . $separator . $markup;
        }
    }
}
