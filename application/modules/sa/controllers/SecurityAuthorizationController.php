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
 * The index controller implements the default action when no specific request
 * is made.
 *
 * @author     Andrew Reeves <andrew.reeves@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    SA
 */
class Sa_SecurityAuthorizationController extends Fisma_Zend_Controller_Action_Object
{
    /**
     * The main name of the model.
     * 
     * This model is the main subject which the controller operates on.
     * 
     * @var string
     */
    protected $_modelName = 'SecurityAuthorization';

    /**
     * @return void
     */
    public function indexAction()
    {
        $this->_forward('list');
    }

    /**
     * Return array of the collection.
     * 
     * @param Doctrine_Collections $rows The spepcific Doctrine_Collections object
     * @return array The array representation of the specified Doctrine_Collections object
     */
    public function handleCollection($rows)
    {
        $result = $rows->toArray();
        foreach ($rows as $k => $v) {
            $result[$k]['system'] = $v->System->Organization->name;
        }
        return $result;
    }

    /**
     * Override parent to add in extra relations
     *
     * @param Doctrine_Query $query Query to be modified
     * @return Doctrine_Collection Results of search query
     */
    public function executeSearchQuery(Doctrine_Query $query)
    {
        // join in System relation
        $alias = $query->getRootAlias();
        $query->leftJoin($alias . '.System system');
        $query->leftJoin('system.Organization org');
        $query->addSelect('system.id, org.id, org.name');
        return parent::executeSearchQuery($query);
    }

}
