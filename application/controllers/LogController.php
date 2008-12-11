<?php
/**
 * Copyright (c) 2008 Endeavor Systems, Inc.
 *
 * This file is part of OpenFISMA.
 *
 * OpenFISMA is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * OpenFISMA is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with OpenFISMA.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Woody Lee <woody712@users.sourceforge.net>
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 * @version   $Id$
 */

/**
 * The log controller deals with managing user logs on the system.
 *
 * @package    Controller
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 */
class LogController extends PoamBaseController
{
    protected $_log = null;

    function init()
    {
        parent::init();
        $this->_log = new Log();
    }

    /**
     * Render the form for searching the logs.
     */
    public function searchboxAction()
    {
        $this->_acl->requirePrivilege('admin_users', 'read');
        
        $user = new User();
        $userCount = $user->count();
        // These are the fields which can be searched, the key is the physical
        // name and the value is the logical name which is displayed in the
        // interface.
        $criteria = array(
            'event'=>'Event Name',
            'account'=>'Account Name');
        
        $accountLog = $this->_log->select();
        $query = $accountLog
                      ->from(array('al'=>'account_logs'), array('count'=>'COUNT(al.user_id)'));

        $fid = $this->_request->getParam('fid');
        $qv = $this->_request->getParam('qv');
        $urlLink = '';
        if (!empty($qv) && in_array($fid, array('event', 'account'))) {
            $query->setIntegrityCheck(false)
                  ->joinLeft(array('u'=>'users'), 'al.user_id = u.id', array())
                  ->where("$fid = '$qv'");
            $urlLink = "/fid/$fid/qv/$qv";
        }
        $ret = $this->_log->fetchRow($query);
        $logCount = $ret->count;
        $this->_paging['totalItems'] = $logCount;
        $this->_paging['fileName'] = "/panel/log/sub/view/p/%d".$urlLink;
        $pager = &Pager::factory($this->_paging);
        $temp = $pager->getLinks();

        // Assign view outputs
        $this->view->assign('criteria', $criteria);
        $this->view->assign('fid', $fid);
        $this->view->assign('qv', $qv);
        $this->view->assign('total', $userCount);
        $this->view->assign('postAction', '/panel/log/sub/view/');
        $this->view->assign('links', $pager->getLinks());
    }
    
    /**
     * List all the logs.
     */
    public function viewAction()
    {
        $this->_acl->requirePrivilege('admin_users', 'read');
        
        // Set up the query to get the full list of user logs
        $qry = $this->_log->select()
                  ->from(array('al' => 'account_logs'),
                         array('timestamp', 'event', 'user_id', 'message'))
                  ->setIntegrityCheck(false)
                  ->joinLeft(array('u'=>'users'),
                             'al.user_id = u.id',
                             'account');

        $qv = $this->_request->getParam('qv');
        $fid = $this->_request->getParam('fid');
        if (!empty($qv) && in_array($fid, array('event', 'account'))) {
            $qry->where("$fid = '$qv'");
        }
        $qry->order("timestamp DESC");
        $qry->limitPage($this->_paging['currentPage'], 
                        $this->_paging['perPage']);
        $logList = $this->_log->fetchAll($qry);
        // Assign view outputs
        $this->view->assign('logList', $logList);
    }

}
