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
            $result[$k]['system'] = $v->Organization->name;
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
        $query->leftJoin($alias . '.Organization org');
        $query->addSelect('org.id, org.name');
        return parent::executeSearchQuery($query);
    }

    /**
     * Hooks for manipulating and saving the values retrieved by Forms
     *
     * @param Zend_Form $form The specified form
     * @param Doctrine_Record|null $subject The specified subject model
     * @return integer ID of the object saved. 
     * @throws Fisma_Zend_Exception if the subject is not instance of Doctrine_Record
     */
    protected function saveValue($form, $subject=null)
    {
        // call default implementation and save the ID
        $saId = parent::saveValue($form, $subject);

        // if subject null, we're creating a new object and we need to populate relations
        if (is_null($subject)) {
            $sa = Doctrine::getTable('SecurityAuthorization')->find($saId);
            $impact = $sa->impact;
            $controlLevels = array();
            switch($impact) {
                case 'HIGH':
                    $controlLevels[] = 'HIGH';
                case 'MODERATE':
                    $controlLevels[] = 'MODERATE';
                default:
                    $controlLevels[] = 'LOW';
            }
            $catalogId = Fisma::configuration()->getConfig('default_security_control_catalog_id');

            // associate suggested controls
            $controls = Doctrine_Query::create()
                ->from('SecurityControl')
                ->whereIn('controlLevel', $controlLevels)
                ->andWhere('securityControlCatalogId = ?', array($catalogId))
                ->execute();
            foreach ($controls as $control) {
                $sacontrol = new SaSecurityControl();
                $sacontrol->securityAuthorizationId = $sa->id;
                $sacontrol->securityControlId = $control->id;
                $sacontrol->save();
                $sacontrol->free();
            }
            $controls->free();
            unset($controls);
 
            // associate suggested control enhancements
            $controlEnhancements = Doctrine_Query::create()
                ->from('SecurityControlEnhancement sce')
                ->innerJoin('sce.Control control')
                ->whereIn('sce.level', $controlLevels)
                ->andWhere('control.securityControlCatalogId = ?', array($catalogId))
                ->execute();
            foreach ($controlEnhancements as $ce) {
                $sace = new SaSecurityControlEnhancement();
                $sace->securityAuthorizationId = $sa->id;
                $sace->securityControlEnhancementId = $ce->id;
                $sace->save();
                $sace->free();
            }
            $controlEnhancements->free();
        }
        return $saId;
    }

}
