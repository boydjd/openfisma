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
 * @author    Ryan Yang <ryan@users.sourceforge.net>
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 * @version   $Id$
 * @package   Controller
 */

/**
 * Handles CRUD for "system" objects.
 *
 * @package   Controller
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 */
class SystemController extends BaseController
{
    protected $_modelName = 'System';

    /**
     * Returns the standard form for creating, reading, and updating systems.
     *
     * @return Zend_Form
     */
    public function getForm()
    {
        $form = Fisma_Form_Manager::loadForm('system');
        $organizationTreeObject = Doctrine::getTable('Organization')->getTree();
        $q = Doctrine_Query::create()
                ->select('o.*')
                ->from('Organization o')
                ->where('o.orgType != ?', 'system');
        $organizationTreeObject->setBaseQuery($q);
        $organizationTree = $organizationTreeObject->fetchTree();
        if (!empty($organizationTree)) {
            foreach ($organizationTree as $organization) {
                $value = $organization['id'];
                $text = str_repeat('--', $organization['level']) . $organization['name'];
                $form->getElement('organizationid')->addMultiOptions(array($value => $text));
            }
        } else {
            $form->getElement('organizationid')->addMultiOptions(array(0 => 'NONE'));
        }
        
        $systemTable = Doctrine::getTable('System');
        
        $array = $systemTable->getEnumValues('confidentiality');
        $form->getElement('confidentiality')->addMultiOptions(array_combine($array, $array));
        
        $array = $systemTable->getEnumValues('integrity');
        $form->getElement('integrity')->addMultiOptions(array_combine($array, $array));
        
        $array = $systemTable->getEnumValues('availability');
        $form->getElement('availability')->addMultiOptions(array_combine($array, $array));
        
        $type = $systemTable->getEnumValues('type');
        $form->getElement('type')->addMultiOptions(array_combine($type, $type));
        
        return Fisma_Form_Manager::prepareForm($form);
    }

    protected function setForm($system, $form) 
    {
        $system->name = $system->Organization[0]->name;
        $system->nickname = $system->Organization[0]->nickname;
        $system->description = $system->Organization[0]->description;
        return parent::setForm($system, $form);
    }

    /**
     * list the systems from the search, 
     * if search none, it list all systems
     *
     */
    public function searchAction()
    {
        Fisma_Acl::requirePrivilege('system', 'read');
        
        $value = trim($this->_request->getParam('keywords'));
        
        $sortBy = $this->_request->getParam('sortby', 'name');
        // Replace the HYDRATE_SCALAR alias syntax with the regular Doctrine alias syntax
        $sortBy = str_replace('_', '.', $sortBy);
        $order = $this->_request->getParam('order', 'ASC');
        
        if (!in_array(strtolower($order), array('asc', 'desc'))) {
            /** 
             * @todo english 
             */
            throw new Fisma_Exception('invalid page');
        }
        
        $q = Doctrine_Query::create()
             ->select('s.id, o.name, o.nickname, s.type, s.confidentiality, s.integrity, s.availability, s.fipsCategory')
             ->from('Organization o')
             ->leftJoin('o.System s')
             ->where('o.orgType = ?', 'system')
             ->orderBy("$sortBy $order")
             ->limit($this->_paging['count'])
             ->offset($this->_paging['startIndex'])
             ->setHydrationMode(Doctrine::HYDRATE_SCALAR);

        if (!empty($value)) {
            $this->_helper->searchQuery($value, 'system');
            $cache = $this->getHelper('SearchQuery')->getCacheInstance();
            // get search results in ids
            $systemIds = $cache->load($this->_me->id . '_system');
            if (empty($systemIds)) {
                // set ids as a not exist value in database if search results is none.
                $systemIds = array(-1);
            }
            $q->whereIn('u.id', $systemIds);
        }

        $totalRecords = $q->count();
        $organizations = $q->execute();

        $tableData = array('table' => array(
            'recordsReturned' => count($organizations),
            'totalRecords' => $totalRecords,
            'startIndex' => $this->_paging['startIndex'],
            'sort' => $sortBy,
            'dir' => $order,
            'pageSize' => $this->_paging['count'],
            'records' => $organizations
        ));

        $this->_helper->json($tableData);
    }
}
