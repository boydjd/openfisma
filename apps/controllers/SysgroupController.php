<?php
/**
 * Sys_GroupController.php
 *
 * System_group Controller
 *
 * @package Controller
 * @author     Ryan rayn at users.sourceforge.net
 * @copyright  (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license    http://www.openfisma.org/mw/index.php?title=License
 * @version $Id$
 */
require_once CONTROLLERS . DS . 'SecurityController.php';
require_once MODELS . DS . 'sysgroup.php';
require_once 'Pager.php';
require_once 'Zend/Filter/Input.php';
require_once 'Zend/Filter/StringTrim.php';
require_once 'Zend/Form.php';
require_once 'Zend/Form/Element/Text.php';
require_once 'Zend/Form/Element/Submit.php';
require_once 'Zend/Form/Element/Reset.php';
require_once 'Zend/Form/Element/Button.php';
/**
 * Sysgroup Controller
 * @package Controller
 * @author     Ryan rayn at users.sourceforge.net
 * @copyright  (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license    http://www.openfisma.org/mw/index.php?title=License
 */
class SysgroupController extends SecurityController
{
    private $_paging = array(
        'mode' => 'Sliding',
        'append' => false,
        'urlVar' => 'p',
        'path' => '',
        'currentPage' => 1,
        'perPage' => 20
    );

    public function init()
    {
        parent::init();
        $this->_sysgroup = new Sysgroup();
    }
    public function preDispatch()
    {
        $req = $this->getRequest();
        $this->_paging_base_path = $req->getBaseUrl()
                                   . '/panel/sysgroup/sub/list';
        $this->_paging['currentPage'] = $req->getParam('p', 1);
        if (!in_array($req->getActionName() , array(
            'login',
            'logout'
        ))) {
            // by pass the authentication when login
            parent::preDispatch();
        }
    }
    /*
     * Get system group form object for system group creation and modification
     * 
     * @param string $method: show submit button name , create or edit
     * @return  Zend_Form
     */
    public function getForm ($method)
    {
        $form = new Zend_Form();
        $sysgroupName = new Zend_Form_Element_Text('name');
        $sysgroupName->setLabel('* System Group Name:')
            ->setRequired(TRUE)
            ->addValidators(array(array('NotEmpty' , true)));
        $sysgroupNickname = new Zend_Form_Element_Text('nickname');
        $sysgroupNickname->setLabel('* System Group Nickname:')
            ->setRequired(TRUE)
            ->addValidators(array(array('NotEmpty' , true)));
        $submit = new Zend_Form_Element_Submit($method);
        $submit->setDecorators(array(
            array('ViewHelper' , array('helper' => 'formSubmit')) ,
            array('HtmlTag' , array('tag' => 'span'))));
        $reset = new Zend_Form_Element_Reset('reset');
        $reset->setDecorators(array(array('ViewHelper' ,
            array('helper' => 'formReset')) ,
            array('HtmlTag' , array('tag' => 'dd'))));
        $form->addElements(array($sysgroupName , $sysgroupNickname ,
            $submit , $reset));
        $form->setElementFilters(array('StringTrim' , 'StripTags'));
        $form->setMethod('post');
        return $form;
    }
    public function searchboxAction()
    {
        $req = $this->getRequest();
        $fid = $req->getParam('fid');
        $qv = $req->getParam('qv');
        $query = $this->_sysgroup->select()->from(array(
            'sg' => 'system_groups'
        ) , array(
            'count' => 'COUNT(sg.id)'
        ))->where('sg.is_identity = 0');
        $res = $this->_sysgroup->fetchRow($query)->toArray();
        $count = $res['count'];
        $this->_paging['totalItems'] = $count;
        $this->_paging['fileName'] = "{$this->_paging_base_path}/p/%d";
        $pager = & Pager::factory($this->_paging);
        $this->view->assign('fid', $fid);
        $this->view->assign('qv', $qv);
        $this->view->assign('total', $count);
        $this->view->assign('links', $pager->getLinks());
        $this->render();
    }
    public function listAction()
    {
        $req = $this->getRequest();
        $field = $req->getParam('fid');
        $value = trim($req->getParam('qv'));
        $query = $this->_sysgroup->select()->from('system_groups', '*')
                                           ->where('is_identity = 0');
        if (!empty($value)) {
            $query->where("$field = ?", $value);
        }
        $query->order('name ASC')->limitPage($this->_paging['currentPage'],
                                             $this->_paging['perPage']);
        $sysgroup_list = $this->_sysgroup->fetchAll($query)->toArray();
        $this->view->assign('sysgroup_list', $sysgroup_list);
        $this->render();
    }
    public function createAction()
    {
        $form = $this->getForm('create');
        $sysGroup = $this->_request->getPost();
        if ($sysGroup) {
            if ($form->isValid($sysGroup)) {
                $sysGroup = $form->getValues();
                unset($sysGroup['create']);
                unset($sysGroup['reset']);
                $sysGroup['is_identity'] = 0;
                $res = $this->_sysgroup->insert($sysGroup);
                if (! $res) {
                    //@REVIEW 2 lines
                    $msg = "Failure in creation";
                    $model = self::M_WARNING;
                } else {
                    $msg = "The system group is created";
                    $model = self::M_NOTICE;
                }
                $this->message($msg, $model);
                $form = $this->getForm('create');
            } else {
                $form->populate($sysGroup);
            }
        }
        $this->view->form = $form;
        $this->render('sysgroupform');
    }
    public function deleteAction()
    {
        $req = $this->getRequest();
        $id = $req->getParam('id');
        $db = $this->_sysgroup->getAdapter();
        $qry = $db->select()->from('systemgroup_systems')
            ->where('sysgroup_id = ' . $id);
        $result = $db->fetchCol($qry);
        $model = self::M_WARNING;
        if (!empty($result)) {
            //@REVIEW 3 lines
            $msg = 'Deletion aborted! One or more systems exist within it.';
        } else {
            $res = $this->_sysgroup->delete('id = ' . $id);
            if (!$res) {
                $msg = "Failure during deletion";
            } else {
                $msg = "The system group is deleted";
                $model = self::M_NOTICE;
            }
        }
        $this->message($msg, $model);
        $this->_forward('list');
    }
    public function viewAction()
    {
        $id = $this->_request->getParam('id');
        $res = $this->_sysgroup->find($id)->toArray();
        $sysgroup = $res[0];
        $this->view->assign('id', $id);
        $this->view->assign('sysgroup', $sysgroup);
        $this->render();
    }
    public function editAction ()
    {
        $form = $this->getForm('save');
        $id = $this->_request->getParam('id');
        $sysgroup = $this->_request->getPost();
        if ($sysgroup) {
            if ($form->isValid($sysgroup)) {
                $sysgroup = $form->getValues();
                unset($sysgroup['save']);
                unset($sysgroup['reset']);
                $res = $this->_sysgroup->update($sysgroup, 'id = ' . $id);
                if ($res) {
                    //@REVIEW 2 lines
                    $msg = "The system group is saved";
                    $model = self::M_NOTICE;
                } else {
                    $msg = "Nothing changes";
                    $model = self::M_WARNING;
                }
                $this->message($msg, $model);
            } else {
                $form->populate($sysgroup);
            }
        } else {
            $res = $this->_sysgroup->find($id)->toArray();
            $sysgroup = $res[0];
            $form->setDefaults($sysgroup);
        }
        $this->view->form = $form;
        $this->render('sysgroupform');
    }
}
