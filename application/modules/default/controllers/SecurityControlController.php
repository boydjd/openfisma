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
 */
class SecurityControlController extends Fisma_Zend_Controller_Action_Object
{
    /**
     * The main name of the model.
     *
     * This model is the main subject which the controller operates on.
     *
     * @var string
     */
    protected $_modelName = 'SecurityControl';

    /**
     * Override parent to disable ACL checks. This model does not contain sensitive data and the edit and delete
     * actions are disabled.
     */
    protected $_enforceAcl = false;

    /**
     * Set up context switch
     */
    public function init()
    {
        parent::init();

        $this->_helper->fismaContextSwitch()
                      ->addActionContext('autocomplete', 'json')
                      ->addActionContext('search', 'json')
                      ->initContext();
    }

    /**
     * View information for a particular control
     *
     * @GETAllowed
     */
    public function viewAction()
    {
        $securityControlId = $this->getRequest()->getParam('id');

        $securityControl = Doctrine::getTable('SecurityControl')->find($securityControlId);

        if (!$securityControl) {
            throw new Fisma_Zend_Exception("No security control with id ($securityControlId) found.");
        }

        $fromSearchParams = $this->_getFromSearchParams($this->_request);
        $this->view->toolbarButtons = $this->getToolbarButtons($securityControl);
        $this->view->searchButtons = $this->getSearchButtons($securityControl, $fromSearchParams);
        $this->view->securityControl = $securityControl;
    }

    /**
     * Override parent to disable this action
     *
     * @GETAllowed
     */
    public function editAction()
    {
        throw new Fisma_Zend_Exception("Edit is not allowed on this controller");
    }

    /**
     * Override parent to disable this action
     */
    public function deleteAction()
    {
        throw new Fisma_Zend_Exception("Delete is not allowed on this controller");
    }

    /**
     * A helper action for autocomplete text boxes
     *
     * @GETAllowed
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
                        ->where("CONCAT(sc.code, ' ', sc.name, ' [', c.name, ']') LIKE ?", "%$keyword%")
                        ->andWhere("c.published = ?", true)
                        ->orderBy("sc.code")
                        ->setHydrationMode(Doctrine::HYDRATE_ARRAY);

        $this->view->controls = $controlQuery->execute();
    }

    /**
     * Render a single control as a table
     *
     * This view can also be invoked as a partial, so it can be embedded into other views or fetched with an XHR.
     *
     * @GETAllowed
     */
    public function singleControlAction()
    {
        $this->_helper->layout->disableLayout(true);

        $securityControlId = $this->getRequest()->getParam('id');

        $this->view->securityControl = Doctrine::getTable('SecurityControl')->find($securityControlId);
    }

    /**
     * Override parent to provide proper human-readable name for SystemDocument class
     */
    public function getSingularModelName()
    {
        return 'Security Control';
    }

    /**
     * Override parent to provide proper human-readable name for SystemDocument class
     */
    public function getPluralModelName()
    {
        return 'Security Controls';
    }

    /**
     * Override to remove the "Create New" button
     *
     * @param Fisma_Doctrine_Record $record The object for which this toolbar applies, or null if not applicable
     * @return array Array of Fisma_Yui_Form_Button
     */
    public function getToolbarButtons(Fisma_Doctrine_Record $record = null, $fromSearchParams = null)
    {
        $buttons = parent::getToolbarButtons($record, $fromSearchParams);

        unset($buttons['create']);

        return $buttons;
    }
}
