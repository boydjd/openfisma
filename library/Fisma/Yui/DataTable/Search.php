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
 * A PHP wrapper for a YUI DataTable for search.
 * 
 * @author     Andrew Reeves <andrew.reeves@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Yui
 */
class Fisma_Yui_DataTable_Search extends Fisma_Yui_DataTable_Remote
{
    /**
     * Render the datatable with HTML and/or Javascript
     *
     * Overrides parent class behavior.
     * 
     * @return string
     */
    public function render()
    {
        $this->_validate();
        
        $view = Zend_Layout::getMvcInstance()->getView();

        $uniqueId = uniqid();
               
        $data = array(
            'clickEventBaseUrl' => $this->_clickEventBaseUrl,
            'clickEventVariableName' => $this->_clickEventVariableName,
            'columns' => $this->getColumns(),
            'columnDefinitions' => $this->_getYuiColumnDefinitions(),
            'containerId' => $uniqueId . "_container",
            'dataUrl' => $this->_dataUrl,
            'deferData' => $this->_deferData,
            'initialSortColumn' => $this->_initialSortColumn,
            'renderEventFunction' => $this->_renderEventFunction,
            'requestConstructor' => $this->_requestConstructor,
            'resultVariable' => $this->_resultVariable,
            'rowCount' => $this->_rowCount,
            'sortDirection' => ($this->_sortAscending ? 'asc' : 'desc')
        );

        return $view->partial('yui/data-table-search.phtml', 'default', $data);
    }
}
