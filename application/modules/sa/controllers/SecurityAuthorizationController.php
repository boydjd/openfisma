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
        $sa = $subject;

        /** 
         * if subject is null we need to add in the impact from the system before passing the form onto the save
         * method.
         */
        if (is_null($subject)) {
            // fetch the system and use its impact values to set the impact of this SA
            $org = Doctrine::getTable('Organization')->find($form->getValue(sysOrgId));
            $system = $org->System;
            if (empty($system)) {
                throw new Fisma_Exception('A non-system was set to the Security Authorization');
            }

            $sa = new SecurityAuthorization();
    
            $impacts = array(
                $system->confidentiality,
                $system->integrity,
                $system->availability
            );
            if (in_array('HIGH', $impacts)) {
                $sa->impact = 'HIGH';
            } else if (in_array('MODERATE', $impacts)) {
                $sa->impact = 'MODERATE';
            } else {
                $sa->impact = 'LOW';
            }
        }
        
        // call default implementation and save the ID
        $saId = parent::saveValue($form, $sa);

        // if subject null, we're creating a new object and we need to populate relations
        if (is_null($subject)) {
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
                ->from('SecurityControl sc')
                ->where('sc.securityControlCatalogId = ?', array($catalogId))
                ->andWhereIn('sc.controlLevel', $controlLevels)
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

            // associate suggested enhancements
            $sacontrols = Doctrine_Query::create()
                ->from('SaSecurityControl sasc')
                ->leftJoin('sasc.SecurityControl sc')
                ->leftJoin('sc.Enhancements sce')
                ->where('sasc.securityAuthorizationId = ?', $sa->id)
                ->andWhereIn('sce.level', $controlLevels)
                ->execute();
            foreach ($sacontrols as $sacontrol) {
                $control = $sacontrol->SecurityControl;
                foreach ($control->Enhancements as $ce) {
                    $sace = new SaSecurityControlEnhancement();
                    $sace->securityControlEnhancementId = $ce->id;
                    $sace->saSecurityControlId = $sacontrol->id;
                    $sace->save();
                    $sace->free();
                }
                $sacontrol->free();
            }
            $sacontrols->free();
            unset($sacontrols);
        }

        return $saId;
    }
   
}
