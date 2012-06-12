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
 * ContactController
 *
 * @author     Andrew Reeves <andrew.reeves@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2012 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Controller
 */
class ContactController extends Fisma_Zend_Controller_Action_Object
{
    /**
     * The main name of the model.
     *
     * @var string
     */
    protected $_modelName = 'Poc';

    /**
     * Override to provide a better singular name
     *
     * @return string
     */
    public function getSingularModelName()
    {
        return 'Contact';
    }

    /**
     * Override base class to prevent deletion of POC objects
     *
     * @return boolean
     */
    protected function _isDeletable()
    {
        return false;
    }

    /**
     * Set up context switch
     */
    public function init()
    {
        parent::init();

        $this->_helper->fismaContextSwitch()
                      ->addActionContext('create', 'json')
                      ->addActionContext('autocomplete', 'json')
                      ->addActionContext('tree-data', 'json')
                      ->initContext();
    }

    /**
     * Override to fill in option values for the select elements, etc.
     *
     * @param string|null $formName The name of the specified form
     * @return Zend_Form The specified form of the subject model
     */
    public function getForm($formName = null)
    {
        $form = parent::getForm($formName);

        $authType = Fisma::configuration()->getConfig('auth_type');
        if ($authType == 'database') {
            // Remove the lookup and separator elements
            $form->removeElement('lookup');
            $form->removeElement('separator');
        }

        // Populate <select> for responsible organization
        $organizations = Doctrine::getTable('Organization')->getOrganizationSelectQuery(true)->execute();
        $selectArray = array('' => '') + $this->view->systemSelect($organizations);
        $form->getElement('reportingOrganizationId')->addMultiOptions($selectArray);

        return $form;
    }

    /**
     * A helper action for autocomplete text boxes
     *
     * @GETAllowed
     */
    public function autocompleteAction()
    {
        $keyword = $this->getRequest()->getParam('keyword');

        $pocQuery = Doctrine_Query::create()
                    ->from('Poc p')
                    ->select("p.id, p.displayName AS name")
                    ->where('p.displayName LIKE ?', '%'.$keyword.'%')
                    ->andWhere('(p.lockType IS NULL OR p.lockType <> ?)', 'manual')
                    ->orderBy("p.nameFirst")
                    ->setHydrationMode(Doctrine::HYDRATE_ARRAY);

        $this->view->pointsOfContact = $pocQuery->execute();
    }

    /**
     * Display the form without any layout
     *
     * @GETAllowed
     */
    public function formAction()
    {
        $this->_helper->layout()->disableLayout();

        // The standard form needs to be modified to work inside a modal yui dialog
        $form = $this->getForm();
        $submit = $form->getElement('save');
        $submit->onClickFunction = 'Fisma.Finding.createPoc';

        $this->view->form = $form;
    }

    /**
     * Override _viewObject to work around the permission wonkiness.
     *
     * A Contact can also be a User. If a person has the read/poc privilege but not read/user, then the person won't be
     * able to view a User object. So we work around that right here.
     */
    protected function _viewObject()
    {
        $this->_acl->requirePrivilegeForClass('read', 'Poc');
        $id = $this->getRequest()->getParam('id');
        $fromSearchParams = $this->_getFromSearchParams($this->_request);
        $fromSearchUrl = $this->_helper->makeUrlParams($fromSearchParams);
        if ($subject = $this->_getSubject($id)) {
            if (get_class($subject) == 'User') {
                $this->_redirect('/user/view/id/' . $id . $fromSearchUrl);
            }
        }

        $this->_enforceAcl = false;
        parent::_viewObject();
        $this->_enforceAcl = true;
    }

    /**
     * Override _editObject to work around the permission wonkiness.
     *
     * A Contact can also be a User. If a person has the update/poc privilege but not update/user, then the person
     * won't be able to modify a User object. So we work around that right here.
     */
    protected function _editObject()
    {
        $this->_acl->requirePrivilegeForClass('update', 'Poc');

        $this->_enforceAcl = false;
        parent::_editObject();
        $this->_enforceAcl = true;
    }

    /**
     * Add the "Contact Hierarchy" button
     *
     * @param Fisma_Doctrine_Record $record The object for which this toolbar applies, or null if not applicable
     * @return array Array of Fisma_Yui_Form_Button
     */
    public function getToolbarButtons(Fisma_Doctrine_Record $record = null, $first = null, $last = null)
    {
        $buttons = array();
        $view = $this->view;

        if ($this->_acl->hasPrivilegeForClass('read', $this->getAclResourceName())) {
            if ($this->getRequest()->getActionName() === 'list') {
                $buttons['tree'] = new Fisma_Yui_Form_Button_Link(
                    'pocTreeButton',
                    array(
                        'value' => 'Tree View',
                        'href' => $this->getBaseUrl() . '/tree',
                        'imageSrc' => '/images/tree_view.png'
                    )
                );
            }
            if ($this->getRequest()->getActionName() === 'tree') {
                $buttons['list'] = new Fisma_Yui_Form_Button_Link(
                    'pocListButton',
                    array(
                        'value' => 'List View',
                        'href' => $this->getBaseUrl() . '/list',
                        'imageSrc' => '/images/list_view.png'
                    )
                );
            }
        }

        if ($this->_acl->hasPrivilegeForClass('create', 'User')) {
            if ($this->getRequest()->getActionName() !== 'view') {
                $buttons['createUser'] = new Fisma_Yui_Form_Button_Link(
                    'createUserButton',
                    array(
                        'value' => 'Create New User',
                        'href' => '/user/create',
                        'imageSrc' => '/images/create.png'
                    )
                );
            }
        }

        if (!empty($record)) {
            $buttons['convert'] = new Fisma_Yui_Form_Button_Link(
                'toolbarConvertButton',
                array(
                    'value' => 'Convert to User',
                    'href' => $this->view->url(array('controller' => 'user', 'action' => 'create', 'id' => $record->id))
                )
            );
        }

        $buttons = array_merge($buttons, parent::getToolbarButtons($record));

        return $buttons;
    }

    /**
     * Display organizations and Contacts in tree mode for quick restructuring of the
     * Contact hierarchy.
     *
     * @GETAllowed
     */
    public function treeAction()
    {
        $this->_acl->requirePrivilegeForClass('read', 'Poc');

        $this->view->toolbarButtons = $this->getToolbarButtons();

        $this->view->csrfToken = $this->_helper->csrf->getToken();

        // We're already on the tree screen, so don't show a "view tree" button
        unset($this->view->toolbarButtons['tree']);
    }

    /**
     * Returns a JSON object that describes the Contact tree
     *
     * @GETAllowed
     */
    public function treeDataAction()
    {
        $this->_acl->requirePrivilegeForClass('read', 'Poc');

        $this->view->treeData = $this->_getPocTree();
    }

    /**
     * Gets the organization tree for the current user.
     *
     * @return array The array representation of organization tree
     */
    protected function _getPocTree()
    {
        // Get a list of Contacts
        $pocQuery = Doctrine_Query::create()
                    ->select('p.id, p.username, p.nameFirst, p.nameLast, p.type, p.reportingOrganizationId')
                    ->from('Poc p')
                    ->orderBy('p.reportingOrganizationId, p.username')
                    ->where('p.reportingOrganizationId IS NOT NULL')
                    ->setHydrationMode(Doctrine::HYDRATE_SCALAR);
        $pocs = $pocQuery->execute();

        // Group Contacts by organization ID
        $pocsByOrgId = array();

        foreach ($pocs as $poc) {
            $orgId = $poc['p_reportingOrganizationId'];

            if (isset($pocsByOrgId[$orgId])) {
                $pocsByOrgId[$orgId][] = $poc;
            } else {
                $pocsByOrgId[$orgId] = array($poc);
            }
        }

        // Get a tree of organizations
        $orgBaseQuery = Doctrine_Query::create()
                        ->from('Organization o')
                        ->select('o.name, o.nickname, ot.nickname, s.type, s.sdlcPhase')
                        ->leftJoin('o.OrganizationType ot')
                        ->where('ot.nickname <> ?', 'system')
                        ->orderBy('o.lft');

        $orgTree = Doctrine::getTable('Organization')->getTree();
        $orgTree->setBaseQuery($orgBaseQuery);
        $organizations = $orgTree->fetchTree();
        $orgTree->resetBaseQuery();

        // Merge organizations and Contacts and return.
        $organizationTree = $this->toHierarchy($organizations, $pocsByOrgId);

        return $organizationTree;
    }

    /**
     * Transform the flat array returned from Doctrine's nested set into a nested array
     *
     * Doctrine should provide this functionality in a future
     *
     * @param Doctrine_Collection $collection The collection of organization record to hierarchy
     * @param array $pocsByOrgId Nested array of Contacts indexed by the Contacts' reporting organization ID
     * @return array The array representation of organization tree
     * @todo review the need for this function in the future
     */
    public function toHierarchy($collection, $pocsByOrgId)
    {
        // Trees mapped
        $trees = array();
        $l = 0;

        // Ensure collection is a tree
        if (!empty($collection)) {
            // Node Stack. Used to help building the hierarchy
            $rootLevel = $collection[0]->level;

            $stack = array();
            foreach ($collection as $node) {
                $item = ($node instanceof Doctrine_Record) ? $node->toArray() : $node;
                $item['level'] -= $rootLevel;
                $item['label'] = $item['nickname'] . ' - ' . $item['name'];
                $item['orgType'] = $node->getType();
                $item['iconId'] = $node->getIconId();
                $item['orgTypeLabel'] = $node->getOrgTypeLabel();
                $item['children'] = array();

                // Merge in any Contacts that report to this organization
                if (isset($pocsByOrgId[$node->id])) {
                    $item['children'] += $pocsByOrgId[$node->id];
                }

                // Number of stack items
                $l = count($stack);
                // Check if we're dealing with different levels
                while ($l > 0 && $stack[$l - 1]['level'] >= $item['level']) {
                    array_pop($stack);
                    $l--;
                }

                if ($l != 0) {
                    if ($node->getNode()->getParent()->name == $stack[$l-1]['name']) {
                        // Add node to parent
                        $i = count($stack[$l - 1]['children']);
                        $stack[$l - 1]['children'][$i] = $item;
                        $stack[] = & $stack[$l - 1]['children'][$i];
                    } else {
                        // Find where the node belongs
                        for ($j = $l; $j >= 0; $j--) {
                            if ($j == 0) {
                                $i = count($trees);
                                $trees[$i] = $item;
                                $stack[] = &$trees[$i];
                            } elseif ($node->getNode()->getParent()->name == $stack[$j-1]['name']) {
                                // Add node to parent
                                $i = count($stack[$j-1]['children']);
                                $stack[$j-1]['children'][$i] = $item;
                                $stack[] = &$stack[$j-1]['children'][$i];
                                break;
                            } elseif ($node->getNode()->getLevel() > 1) {

                                // Find the node's organization parent when its parent is a system.
                                $parent = $this->_getOrganizationParent($node->getNode());

                                if ($parent && $parent->name == $stack[$j-1]['name']) {
                                    // Add node to parent
                                    $i = count($stack[$j-1]['children']);
                                    $stack[$j-1]['children'][$i] = $item;
                                    $stack[] = &$stack[$j-1]['children'][$i];
                                    break;
                                }
                            }
                        }
                    }
                } else {
                    // Assigning the root node
                    $i = count($trees);
                    $trees[$i] = $item;
                    $stack[] = &$trees[$i];
                }
            }
        }

        return $trees;
    }

    /**
     * Get the nearest ancestor with organization type.
     *
     * @param Doctrine_Record $node The nested node.
     * @return mixed Doctrine_Record if found, otherwise false.
     */
    private function _getOrganizationParent($node)
    {
        $ancestors = $node->getAncestors();
        if ($ancestors) {
            for ($i = count($ancestors) - 1; $i >= 0; $i--) {
                if (is_null($ancestors[$i]->systemId)) {
                    return $ancestors[$i];
                }
            }
        }

        return false;
    }

    /**
     * Moves a Contact node from one organization to another.
     *
     * This is used by the YUI tree node to handle drag and drop of organization nodes. It replies with a JSON object.
     */
    public function moveNodeAction()
    {
        $this->_helper->viewRenderer->setNoRender(true);
        $this->_helper->layout()->disableLayout();

        $response = new Fisma_AsyncResponse;

        // Find the source and destination objects from the tree
        $srcId = $this->getRequest()->getParam('src');
        $src = Doctrine::getTable('Poc')->find($srcId);

        $destPocId = $this->getRequest()->getParam('destPoc');
        if ($destPocId) {
            $destPoc = Doctrine::getTable('Poc')->find($destPocId);
            $destOrg = $destPoc->ReportingOrganization;
        } else {
            $destId = $this->getRequest()->getParam('destOrg');
            $destOrg = Doctrine::getTable('Organization')->find($destId);
        }

        if ($src && $destOrg) {
            $src->ReportingOrganization = $destOrg;
            $src->save();
        } else {
            $response->fail("Invalid src, destPoc or destOrg parameter ($srcId, $destPocId, $destOrgId)");
        }

        print Zend_Json::encode($response);
    }
}
