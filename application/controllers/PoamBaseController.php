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
 * @author    Jim Chen <xhorse@users.sourceforge.net>
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 * @version   $Id$
 */

/**
 * ???
 *
 * @package   Controller
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 *
 * @todo Need to explain what the purpose of this controller is.
 * @todo This is not a controller, why is it named PoamBaseController??
 */
class PoamBaseController extends SecurityController
{
    protected $_systemList = null;
    protected $_sourceList = null;
    protected $_networkList = null;
    protected $_poam = null;
    /**
     * The requestor object.
     * To save the labor to initialize it every time.
     */
    protected $_req = null;
    protected $_paging = array(
        'mode' => 'Sliding',
        'append' => false,
        'urlVar' => 'p',
        'path' => '',
        'currentPage' => 1,
        'perPage' => 20
    );
    public function preDispatch()
    {
        parent::preDispatch();
        $this->_poam = new Poam();
        $src = new Source();
        $net = new Network();
        $sys = new System();
        $this->_sourceList = $src->getList('name');
        $tmpList = $sys->getList(array(
            'name',
            'nickname'
        ), $this->_me->systems, 'nickname');
        $this->_networkList = $net->getList('name');
        foreach ($tmpList as $k => $v) {
            $this->_systemList[$k] = "({$v['nickname']}) {$v['name']}";
        }
        $this->_req = $this->getRequest();
        $req = $this->_req;
        $this->_pagingBasePath = $req->getBaseUrl();
        $this->_paging['currentPage'] = $req->getParam('p', 1);
    }
    
    public function makeUrl($criteria)
    {
        $this->_pagingBasePath.= $this->makeUrlParams($criteria);
    }
    
    /**
     * Translate the criteria to a string which can be used in an URL
     *
     * The string can be parsed by the application to form the criteria again later.
     *
     * @param array $criteria
     * @return string
     */
    public function makeUrlParams($criteria)
    {
        $urlPart = '';
        foreach ($criteria as $key => $value) {
            if (!empty($value)) {
                if ($value instanceof Zend_Date) {
                    $urlPart .= '/' . $key . '/' . $value->toString('Ymd') . '';
                } else {
                    $urlPart .= '/' . $key . '/' . urlencode($value) . '';
                }
            }
        }
        return $urlPart;
    }
}
