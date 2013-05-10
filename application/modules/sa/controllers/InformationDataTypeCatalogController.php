<?php
/**
 * Copyright (c) 2013 Endeavor Systems, Inc.
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
 * Handles SA / Information Data Type Catalog
 *
 * @author     Duy K. Bui <duy.bui@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2013 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Form
 */
class Sa_InformationDataTypeCatalogController extends Fisma_Zend_Controller_Action_Object
{
    /**
     * Specify model name
     *
     * @var string
     */
    protected $_modelName = 'InformationDataTypeCatalog';

    /**
     * Return user-friendly name for the model
     *
     * @return string
     */
    public function getSingularModelName()
    {
        return 'Information Data Type Catalog';
    }

    /**
     * Return the form for the CRUD controller
     *
     * @return Fisma_Zend_Form_Default
     */
    public function getForm($formName = null)
    {
        return new Sa_InformationDataTypeCatalogForm();
    }

    /**
     * Add two buttons
     *
     * @param Fisma_Doctrine_Record $record Optional. The record being viewed
     * @param string $fromSearchParams Optional. Extra data for back / next buttons
     * @return array
     */
    public function getToolbarButtons(Fisma_Doctrine_Record $record = NULL, $fromSearchParams = NULL)
    {
        $buttons = parent::getToolbarButtons($record);
        $action = $this->getRequest()->getActionName();
        $fromSearchParams = ($fromSearchParams) ? $fromSearchParams : $this->_getFromSearchParams($this->getRequest());
        $fromSearchUrl = ($fromSearchParams) ? $this->_helper->makeUrlParams($fromSearchParams) : '';
        if ($action === 'view' && $record) {
            $canUpdate = $this->_acl->hasPrivilegeForObject('update', $record);
            if ($canUpdate) {
                $buttons['publishAll'] = new Fisma_Yui_Form_Button(
                    'publishAll',
                    array(
                        'label' => 'Publish',
                        'tooltip' => 'Mark all information data types in this catalog as published.',
                        'icon' => 'eye-open',
                        'onClickFunction' => 'Fisma.Sa.publishCatalog',
                        'onClickArgument' => array(
                            'id' => $record->id,
                            'fromSearchUrl' => $fromSearchUrl
                        )
                    )
                );

                $buttons['unpublishAll'] = new Fisma_Yui_Form_Button(
                    'unpublishAll',
                    array(
                        'label' => 'Unpublish',
                        'tooltip' => 'Mark all information data types in this catalog as not published.',
                        'icon' => 'eye-close',
                        'onClickFunction' => 'Fisma.Sa.unpublishCatalog',
                        'onClickArgument' => array(
                            'id' => $record->id,
                            'fromSearchUrl' => $fromSearchUrl
                        )
                    )
                );
            }
        }

        return $buttons;
    }

    /**
     * Publish all information data types in a catalog
     */
    public function publishAction()
    {
        $this->_setCatalogVisible(1);
    }

    /**
     * Unpublish all information data types in a catalog
     */
    public function unpublishAction()
    {
        $this->_setCatalogVisible(0);
    }

    /**
     * Set the published field for all information data types in a catalog
     *
     * @param bool $visible
     */
    private function _setCatalogVisible($visible)
    {
        $id = $this->getRequest()->getParam('id');
        if (!$id) {
            throw new Fisma_Zend_Exception_User('Please provide the catalog ID.');
        }
        $catalog = Doctrine::getTable('InformationDataTypeCatalog')->find($id);
        if (!$catalog) {
            throw new Fisma_Zend_Exception_User("Invalid catalog ID provided: $id.");
        }
        $fromSearchParams = $this->_getFromSearchParams($this->getRequest());
        $fromSearchUrl = ($fromSearchParams) ? $this->_helper->makeUrlParams($fromSearchParams) : '';

        Doctrine_Query::create()
            ->update('InformationDataType')
            ->set('published', $visible)
            ->where('catalogId = ?', $id)
            ->execute();

        $searchEngine = Zend_Registry::get('search_engine');
        $collection = Doctrine::getTable('InformationDataType')->findByCatalogId($id);
        $searchEngine->indexCollection('InformationDataType', $collection);
        $searchEngine->commit();

        $catalog->updateDenormalizedCounters();
        $catalog->save();

        $this->view->priorityMessenger('All information data types updated successfully.', 'success');
        $this->_redirect("/sa/information-data-type-catalog/view/id/{$id}{$fromSearchUrl}");
    }

    /**
     * Override PreDelete hook to handle "custom" catalog
     *
     * @return void
     */
    public function _preDeleteHook()
    {
        $id = $this->_request->getParam('id');
        $subject = $this->_getSubject($id);
        if ($subject->name === 'Custom') {
            throw new Fisma_Zend_Exception_User('This catalog cannot be deleted.');
        }
    }
}
