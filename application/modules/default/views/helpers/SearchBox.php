<?php
/**
 * Copyright (c) 2010 Endeavor Systems, Inc.
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
 * A view helper which renders a search box
 * 
 * @author     Mark E. Haase <mhaase@endeavorystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    View_Helper
 */
class View_Helper_SearchBox extends Zend_View_Helper_Abstract
{
    /**
     * Renders a search box that can contain customized buttons set by the controller
     * 
     * @param array $toolbarButtons Array of Fisma_Yui_Form_Button, these get displayed in the toolbar
     * @param Zend_Form $searchForm The form used for submitting a search query
     * @return string
     */
    public function searchBox($toolbarButtons, $searchForm = null)
    {
        $view = Zend_Layout::getMvcInstance()->getView();
        
        $viewParameters = array(
            'searchForm' => $searchForm,
            'toolbarButtons' => $toolbarButtons
        );

        return $view->partial('helper/search-box.phtml', 'default', $viewParameters);
    }
}
