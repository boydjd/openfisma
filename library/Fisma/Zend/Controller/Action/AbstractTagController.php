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
 * Base controller to handle Tags
 *
 * @author     Andrew Reeves <andrew.reeves@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2012 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Controller
 */
abstract class Fisma_Zend_Controller_Action_AbstractTagController extends Fisma_Zend_Controller_Action_Security
{
    /**
     * Tag ID
     *
     * @var $_tagId string
     */
    protected $_tagId;

    /**
     * _relatedModels defines a set of models which reference this tag.  These will be use to show counts
     * in the tag list.
     *
     * array(
     *     array(
     *         'model' => 'MODEL NAME',
     *         'column' => 'COLUMN NAME',
     *         'label' => 'TALLY COLUMN HEADER',
     *         'modelControllerPrefix' => 'MODEL CONTROLLER URL PREFIX'
     *     ),
     *     ...
     * )
     *
     * @var array
     */
    protected $_relatedModels = array();

    /**
     * _aclResource
     *
     * @var string
     */
    protected $_aclResource = null;

    /**
     * _aclAction
     *
     * @var string
     */
    protected $_aclAction = null;

    /**
     * Mame of tag to show to user.
     *
     * @var mixed
     */
    protected $_displayName = null;

    /**
     * Create contexts managing service tags via AJAX / JSON request
     *
     * @return void
     */
    public function init()
    {
        $this->_helper->ajaxContext()
             ->addActionContext('create', 'json')
             ->addActionContext('update', 'json')
             ->addActionContext('options', 'html')
             ->initContext();

        parent::init();
    }

    /**
     * Create a new tag
     *
     * @GETAllowed
     * @return void
     */
    public function createAction()
    {
        $this->_acl->requirePrivilegeForClass($this->_aclAction, $this->_aclResource);

        $this->view->result = new Fisma_AsyncResponse;
        $this->view->csrfToken = $this->_helper->csrf->getToken();

        $tag = $this->getRequest()->getParam('tag');
        if (!$tag) {
            $this->view->result->fail('Empty tag');
        } else {
            $tagObj = Doctrine::getTable('Tag')->findOneByTagId($this->_tagId);
            $tags = $tagObj->labels;
            if (in_array($tag, $tags)) {
                $this->view->result->succeed('Tag already defined.');
            } else {
                $tags[] = $tag;
                $tagObj->labels = $tags;
                $tagObj->save();
                $this->view->newRow = $this->_mkRow($tag);
                $this->view->result->succeed();
            }
        }
    }

    /**
     * updateAction
     *
     * @return void
     */
    public function updateAction()
    {
        $this->_acl->requirePrivilegeForClass($this->_aclAction, $this->_aclResource);

        $this->view->result = new Fisma_AsyncResponse;
        $this->view->csrfToken = $this->_helper->csrf->getToken();

        $oldTag = $this->getRequest()->getParam('oldTag');
        $newTag = $this->getRequest()->getParam('newTag');
        if (!$oldTag || !$newTag) {
            $this->view->result->fail('Empty tag(s)');
            return;
        }

        $tagObj = Doctrine::getTable('Tag')->findOneByTagId($this->_tagId);
        $tags = $tagObj->labels;
        $key = array_search($oldTag, $tags);
        if ($key < 0) {
            $this->view->result->fail('Tag not found.');
            return;
        }

        $tags[$key] = $newTag;

        try {
            Doctrine_Manager::connection()->beginTransaction();

            foreach ($this->_relatedModels as $related) {
                Doctrine_Query::create()
                    ->update($related['model'])
                    ->set($related['column'], '?', array($newTag))
                    ->where($related['column'] . ' = ?', $oldTag)
                    ->execute();
            }

            $tagObj->labels = $tags;
            $tagObj->save();

            Doctrine_Manager::connection()->commit();
            $this->view->row = $this->_mkRow($newTag, $this->_getCounts());
            $this->view->result->succeed();
        } catch (Doctrine_Exception $e) {
            Doctrine_Manager::connection()->rollback();
            $this->view->result->fail($e->getMessage(), $e);
        }
    }

    /**
     * deleteAction
     *
     * @return void
     */
    public function deleteAction()
    {
        $this->_acl->requirePrivilegeForClass($this->_aclAction, $this->_aclResource);

        $tag = $this->getRequest()->getParam('tag');
        if (!$tag) {
            throw new Fisma_Zend_Exception_User('Empty tag');
        }

        $tagObj = Doctrine::getTable('Tag')->findOneByTagId($this->_tagId);
        $tags = $tagObj->labels;
        $key = array_search($tag, $tags);
        if ($key < 0) {
            throw new Fisma_Zend_Exception_User('Tag not found.');
        }

        unset($tags[$key]);

        try {
            Doctrine_Manager::connection()->beginTransaction();

            foreach ($this->_relatedModels as $related) {
                Doctrine_Query::create()
                    ->update($related['model'])
                    ->set($related['column'], '?', array(''))
                    ->where($related['column'] . ' = ?', $tag)
                    ->execute();
            }

            $tagObj->labels = $tags;
            $tagObj->save();

            Doctrine_Manager::connection()->commit();
            $this->_redirect($this->_helper->url('list'));
        } catch (Doctrine_Exception $e) {
            Doctrine_Manager::connection()->rollback();
            throw $e;
        }

    }

    /**
     * listAction
     *
     * @GETAllowed
     * @return void
     */
    public function listAction()
    {
        $this->_acl->requirePrivilegeForClass($this->_aclAction, $this->_aclResource);

        $data = array();
        $tags = Doctrine::getTable('Tag')->findOneByTagId($this->_tagId)->labels;
        $counts = $this->_getCounts();

        foreach ($tags as $tag) {
            $data[] = $this->_mkRow($tag, $counts);
        }
        $table = new Fisma_Yui_DataTable_Local();
        $table->addColumn(
            new Fisma_Yui_DataTable_Column('Tag', false, 'YAHOO.widget.DataTable.formatText', null, 'tag')
        );
        foreach ($this->_relatedModels as $key => $related) {
            $table->addColumn(
                new Fisma_Yui_DataTable_Column(
                    $related['label'],
                    false,
                    'Fisma.TableFormat.formatLink',
                    null,
                    "related$key"
                )
            );
        }
        $table->addColumn(new Fisma_Yui_DataTable_Column('Edit', false, 'Fisma.TableFormat.editControl', null, 'edit'))
              ->addColumn(
                    new Fisma_Yui_DataTable_Column('Delete', false, 'Fisma.TableFormat.deleteControl', null, 'delete')
              )
              ->setData($data)
              ->setRegistryName('tagTable');
        $this->view->toolbarButtons = array(new Fisma_Yui_Form_Button(
                'addTag',
                array(
                    'label' => 'Add',
                    'onClickFunction' => 'Fisma.Tag.add',
                    'onClickArgument' => array(
                        'tagId' => $this->_tagId,
                        'addUrl' => $this->getHelper('url')->simple('create', null, null, array('format' => 'json')),
                        'displayName' => $this->_displayName
                    ),
                    'imageSrc' => '/images/create.png'
                )
            ));
        $this->view->csrfToken = $this->_helper->csrf->getToken();
        $this->view->tags = $table;
        $this->renderScript('tag/list.phtml');
    }

    /**
     * optionsAction
     *
     * @GETAllowed
     * @return void
     */
    public function optionsAction()
    {
        $this->view->options = Doctrine::getTable('Tag')->findOneByTagId($this->_tagId)->labels;
        $this->view->selected = $this->getRequest()->getParam("value");
        $this->renderScript('tag/options.phtml');
    }

    protected function _mkRow($tag, $counts = array())
    {
        $row = array('tag' => $tag);
        foreach ($this->_relatedModels as $key => $related) {
            $row['related' . $key] = json_encode(array(
                'displayText' => (isset($counts[$key][$tag]) ? $counts[$key][$tag] : 0) . '',
                'url' => "{$related['modelControllerPrefix']}/list?q=" .
                         "/{$related['column']}/textExactMatch/" .
                         $this->view->escape($tag, 'url')
            ));
        }
        $row['edit'] = 'javascript:Fisma.Tag.rename('
            . $this->view->escape($tag, 'json') . ','
            . $this->view->escape($this->getHelper('url')->simple('update'), 'json') . ')';
        $row['delete'] = $this->getHelper('url')->simple('delete', null, null, array('tag' => $tag));
        return $row;
    }

    protected function _getCounts()
    {
        $counts = array();
        foreach ($this->_relatedModels as $key => $related) {
            $counts[$key] = Doctrine_Query::create()
                ->select ("{$related['column']} AS tag")
                ->addSelect ('count(1) AS cnt')
                ->from ($related['model'])
                ->groupBy ("{$related['column']}")
                ->execute()
                ->toKeyValueArray('tag', 'cnt');
        }
        return $counts;
    }
}
