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
 * The controller handles finding relationship actions
 *
 * @author     Duy K. Bui <duy.bui@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2012 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Controller
 */
class Finding_RelationshipController extends Fisma_Zend_Controller_Action_Security
{
    /**
     * The main name of the model.
     *
     * This model is the main subject which the controller operates on.
     *
     * @var string
     */
    protected $_modelName = 'FindingRelationship';

    /**
     * The orgSystems which are belongs to current user.
     *
     * @var Doctrine_Collection
     */
    protected $_organizations = null;

    /**
     * Create contexts for printable tab views.
     *
     * @return void
     */
    public function init()
    {
        $this->_helper->ajaxContext()
             ->addActionContext('get-form', 'html')
             ->addActionContext('new', 'json')
             ->addActionContext('rename', 'json')
             ->initContext();

        parent::init();
    }

    /**
     * Get the form to add a relationship between findings
     *
     * @GETAllowed
     */
    public function getFormAction()
    {
    }

    /**
     * Add a new link between 2 findings
     *
     * @return void
     */
    public function addAction()
    {
        $id = $this->getRequest()->getParam('thisFindingId');
        $finding = Doctrine::getTable('Finding')->find($id);
        $this->_acl->requirePrivilegeForObject('update_relationship', $finding);
        $dir = $this->getRequest()->getParam('direction');

        $relationship = new FindingRelationship();
        $isDirectAction = FindingRelationship::isDirectAction($this->getRequest()->getParam('startRelationship'));

        if ($isDirectAction) {
            $relationship->startFindingId = $id;
            $relationship->endFindingId = $this->getRequest()->getParam('endFindingId');
        } else {
            $relationship->endFindingId = $id;
            $relationship->startFindingId = $this->getRequest()->getParam('endFindingId');
        }

        $relationship->relationship = FindingRelationship::getFullTag($this->getRequest()->getParam('startRelationship'));
        $relationship->createdByUserId = CurrentUser::getAttribute('id');

        $relationship->save();

        $this->_redirect('/finding/remediation/view/id/' . $id);
    }

    /**
     * Remove a link between 2 findings
     *
     * @return void
     */
    public function removeAction()
    {
        $id = $this->getRequest()->getParam('id');
        $findingId = $this->getRequest()->getParam('findingId');
        $finding = Doctrine::getTable('Finding')->find($findingId);
        $this->_acl->requirePrivilegeForObject('update_relationship', $finding);

        Doctrine::getTable('FindingRelationship')->find($id)->delete();

        $this->_redirect('/finding/remediation/view/id/' . $findingId);
    }

    /**
     * Manage link types
     *
     * @GETAllowed
     * @return void
     */
    public function manageAction()
    {
        $this->_acl->requirePrivilegeForClass('manage_relationships', 'Finding');
        $data = array();
        $tags = explode(',', Fisma::configuration()->getConfig('finding_link_types'));

        foreach ($tags as $tag) {
            $data[] = array(
                'tag' => $tag,
                'uses' => Doctrine::getTable('FindingRelationship')->countByRelationship($tag) . '', //toString
                'edit' => 'javascript:Fisma.Finding.renameTag("' . $this->view->escape($tag, 'javascript') . '")',
                'delete' => '/finding/relationship/delete/tag/' . $tag
            );
        }
        $table = new Fisma_Yui_DataTable_Local();
        $table->addColumn(new Fisma_Yui_DataTable_Column('Type', false, 'YAHOO.widget.DataTable.formatText'))
              ->addColumn(new Fisma_Yui_DataTable_Column('Used', false, 'YAHOO.widget.DataTable.formatText'))
              ->addColumn(new Fisma_Yui_DataTable_Column('Edit', false, 'Fisma.TableFormat.editControl'))
              ->addColumn(new Fisma_Yui_DataTable_Column('Delete', false, 'Fisma.TableFormat.deleteControl'))
              ->setData($data)
              ->setRegistryName('findingLinkTypeTable');
        $this->view->toolbarButtons = $this->getToolbarButtons();
        $this->view->csrfToken = $this->_helper->csrf->getToken();
        $this->view->tags = $table;
    }

    /**
     * Add a new link type via AJAX / JSON
     *
     * @return void
     */
    public function newAction()
    {
        $this->view->result = new Fisma_AsyncResponse;
        $this->view->csrfToken = $this->_helper->csrf->getToken();

        if (!$this->_acl->hasPrivilegeForClass('manage_relationships', 'Finding')) {
            $this->view->result->fail('Invalid permission');
        } else {
            $tag = $this->getRequest()->getParam('tag');
            if (!$tag) {
                $this->view->result->fail('Empty tag');
            } else {
                $tags = explode(',', Fisma::configuration()->getConfig('finding_link_types'));
                if (in_array($tag, $tags)) {
                    $this->view->result->succeed('Tag already defined.');
                } else {
                    $tags[] = $tag;
                    Fisma::configuration()->setConfig('finding_link_types', implode(',', $tags));
                    $this->view->result->succeed();
                }
            }
        }
    }

    /**
     * Rename a link type via AJAX / JSON
     *
     * @return void
     */
    public function renameAction()
    {
        $this->view->result = new Fisma_AsyncResponse;
        $this->view->csrfToken = $this->_helper->csrf->getToken();

        if (!$this->_acl->hasPrivilegeForClass('manage_relationships', 'Finding')) {
            $this->view->result->fail('Invalid permission');
        } else {
            $oldTag = $this->getRequest()->getParam('oldTag');
            $newTag = $this->getRequest()->getParam('newTag');
            if (!$oldTag || !$newTag) {
                $this->view->result->fail('Empty tag(s)');
            } else {
                $tags = explode(',', Fisma::configuration()->getConfig('finding_link_types'));
                $key = array_search($oldTag, $tags);
                if ($key >= 0) {
                    $tags[$key] = $newTag;

                    try {
                        Doctrine_Manager::connection()->beginTransaction();

                        $relationships = Doctrine::getTable('FindingRelationship')->findByRelationship($oldTag);
                        foreach ($relationships as $relationship) {
                            $relationship->relationship = $newTag;
                        }
                        $relationships->save();

                        Fisma::configuration()->setConfig('finding_link_types', implode(',', $tags));

                        Doctrine_Manager::connection()->commit();
                        $this->view->result->succeed();
                    } catch (Doctrine_Exception $e) {
                        Doctrine_Manager::connection()->rollback();
                        $this->view->result->fail($e->getMessage(), $e);
                    }
                } else {
                    $this->view->result->fail('Tag not found.');
                }
            }
        }
    }

    /**
     * Delete a link type via HTML POST
     *
     * @return void
     */
    public function deleteAction()
    {
        $this->_acl->requirePrivilegeForClass('manage_relationships', 'Finding');

        $tag = $this->getRequest()->getParam('tag');
        if (!$tag) {
            throw new Fisma_Zend_Exception_User('Empty tag');
        } else {
            $tags = explode(',', Fisma::configuration()->getConfig('finding_link_types'));
            $key = array_search($tag, $tags);
            if ($key >= 0) {
                unset($tags[$key]);

                try {
                    Doctrine_Manager::connection()->beginTransaction();

                    $relationships = Doctrine::getTable('FindingRelationship')->findByRelationship($tag);
                    foreach ($relationships as $relationship) {
                        $relationship->delete();
                    }
                    //$relationships->save();

                    Fisma::configuration()->setConfig('finding_link_types', implode(',', $tags));

                    Doctrine_Manager::connection()->commit();
                    $this->_redirect('/finding/relationship/manage');
                } catch (Doctrine_Exception $e) {
                    throw $e;
                }
            } else {
                throw new Fisma_Zend_Exception_User('Tag not found.');
            }
        }
    }

    public function getToolbarButtons(Fisma_Doctrine_Record $record = null, $fromSearchParams = null)
    {
        $buttons = array();

        $buttons['newButton'] = new Fisma_Yui_Form_Button(
            'new',
            array(
                'label' => 'Add',
                'onClickFunction' => 'Fisma.Finding.addTag',
                'imageSrc' => '/images/add.png'
            )
        );

        return $buttons;
    }

}
