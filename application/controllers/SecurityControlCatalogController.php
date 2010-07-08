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
 * Search and view the various security control catalogs
 * 
 * @author     Mark E. Haase
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Controllers
 * @version    $Id$
 */
class SecurityControlCatalogController extends SecurityControlCatalogBaseController
{
    /**
     * Set up context switch
     */
    public function init()
    {
        $this->_helper->fismaContextSwitch()
                      ->addActionContext('autocomplete', 'json')
                      ->addActionContext('search', 'json')
                      ->initContext();
    }
    
    /**
     * View information for a particular control
     */
    public function viewAction()
    {
        $securityControlId = $this->getRequest()->getParam('id');
        
        $securityControl = Doctrine::getTable('SecurityControl')->find($securityControlId);
        
        if (!$securityControl) {
            throw new Fisma_Zend_Exception("No security control with id ($securityControlId) found.");
        }
        
        $this->view->securityControl = $securityControl;
    }

    /**
     * List all controls for the specified catalog
     */
    public function listAction()
    {
        $defaultCatalogId = Fisma::configuration()->getConfig('default_security_control_catalog_id');

        $catalogId = (int)$this->getRequest()->getParam('id', $defaultCatalogId);
        
        // Look up the catalog and get its name
        $catalog = Doctrine::getTable('SecurityControlCatalog')->find($catalogId);
        
        if (!$catalog) {
            throw new Fisma_Zend_Exception("Invalid catalog ID ($catalogId)");
        }
        
        $this->view->catalogName = $catalog->name;
        
        // Set up the base data URL, which includes the keyword parameter if it was specified
        $keyword = $this->getRequest()->getParam('keyword');
        
        $dataUrl = "/security-control-catalog/search/id/$catalogId/format/json";
        
        if (!empty($keyword)) {
            $dataUrl .= "/keyword/$keyword";
        }
        
        // Create a YUI data table to display the controls
        $controlTable = new Fisma_Yui_DataTable_Remote();
        
        $controlTable->addColumn(new Fisma_Yui_DataTable_Column('Code', true, null, 'sc_code'))
                     ->addColumn(new Fisma_Yui_DataTable_Column('Name', true, null, 'sc_name'))
                     ->addColumn(new Fisma_Yui_DataTable_Column('Class', true, null, 'sc_class'))
                     ->addColumn(new Fisma_Yui_DataTable_Column('Family', true, null, 'sc_family'))
                     ->addColumn(new Fisma_Yui_DataTable_Column('Priority', true, null, 'sc_priorityCode'))
                     ->setResultVariable('controls')
                     ->setDataUrl($dataUrl)
                     ->setInitialSortColumn('sc_code')
                     ->setSortAscending(true)
                     ->setRowCount(25)
                     ->setClickEventBaseUrl('/security-control-catalog/view/id/')
                     ->setClickEventVariableName('sc_id');
        
        $this->view->controlTable = $controlTable;
        
        $this->view->toolbarForm = $this->_getToolbarForm();
    }
    
    /**
     * A helper action for autocomplete text boxes
     */
    public function autocompleteAction()
    {
        $keyword = $this->getRequest()->getParam('keyword');
        
        $controlQuery = Doctrine_Query::create()
                        ->from('SecurityControl sc')
                        ->innerJoin('sc.Catalog c')
                        ->select(
                            "sc.id,
                            CONCAT(sc.code, ' ', sc.name, ' [', c.name, ']') AS name"
                        )
                        ->where('sc.code LIKE ?', "$keyword%")
                        ->orderBy("sc.code")
                        ->setHydrationMode(Doctrine::HYDRATE_ARRAY);

        $this->view->controls = $controlQuery->execute();
    }
    
    /**
     * Render a single control as a table
     * 
     * This view can also be invoked as a partial, so it can be embedded into other views or fetched with an XHR.
     */
    public function singleControlAction()
    {
        $this->_helper->layout->disableLayout(true);
                
        $securityControlId = $this->getRequest()->getParam('id');
        
        $this->view->securityControl = Doctrine::getTable('SecurityControl')->find($securityControlId);
    }
    
    /**
     * Search the specified control catalog and return all matching controls in JSON context
     */
    public function searchAction()
    {
        // Read parameters and set defaults
        $defaultCatalogId = Fisma::configuration()->getConfig('default_security_control_catalog_id');
        
        $request = $this->getRequest();
        $catalogId = $request->getParam('id', $defaultCatalogId); 
        $sortField = $request->getParam('sort', 'sc_code');
        $sortDir = $request->getParam('dir', 'asc');
        $startRow = $request->getParam('start', 0);
        $maxRows = $request->getParam('count', 25);
        $keyword = $request->getParam('keyword');

        // Convert sort field from doctrine scalar naming convention to object notation (eg. sc_code to sc.code)
        $sortField = str_replace('_', '.', $sortField);
                
        // Get a list of all the controls in this catalog
        $controlQuery = Doctrine_Query::create()
                        ->from('SecurityControl sc')
                        ->select(
                            'sc.id,
                            sc.code, 
                            sc.name,
                            sc.class, 
                            sc.family, 
                            sc.priorityCode'
                        )
                        ->where('securityControlCatalogId = ?', array($catalogId))
                        ->orderBy("$sortField $sortDir")
                        ->limit($maxRows)
                        ->offset($startRow)
                        ->setHydrationMode(Doctrine::HYDRATE_SCALAR);

        // If a keyword is specified, augment the query with it
        if (!empty($keyword)) {
            
            /*
             * Because of the way Lucene handles special characters, and the fact that searching for hyphenated control 
             * names is a common use case, we add special logic to handle queries for a specific control name
             */
            $matches = array();
            if (preg_match('/\w\w\-\d\d/', $keyword, $matches)) {
                
                $controlQuery->andWhere('sc.code LIKE ?', $matches[0]);
            } else {
                $index = new Fisma_Index('SecurityControl');

                $matchedIds = $index->findIds($keyword);

                if (count($matchedIds) == 0) {
                    /*
                     * Doctrine "helpfully" strips out your IN clause if the array is empty, so if there are no hits
                     * in the Lucene index, we need to add an impossible condition to the query.
                     */
                    $controlQuery->andWhere('1 = 2');
                } else {
                    $controlQuery->andWhereIn('sc.id', $matchedIds);                    
                }
            }
        }

        $count = $controlQuery->count();
        $controls = $controlQuery->execute();

        $this->view->controls = $controls;
        $this->view->totalRecords = $count;
    }
}
