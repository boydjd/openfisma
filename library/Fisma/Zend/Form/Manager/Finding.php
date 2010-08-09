<?php
/**
 * Copyright (c) 2008 Endeavor Systems, Inc.
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
 * Fisma_Zend_Form_Manager_Finding 
 * 
 * @uses Fisma_Zend_Form_Manager_Abstract
 * @package Fisma_Zend_Form_Manager 
 * @copyright (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @author Josh Boyd <joshua.boyd@endeavorsystems.com> 
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Fisma_Zend_Form_Manager_Finding extends Fisma_Zend_Form_Manager_Abstract
{
    /**
     * prepareForm 
     */
    public function prepareForm()
    {
        $form = $this->_form;

        $threatLevelOptions = $form->getElement('threatLevel')->getMultiOptions();
        $form->getElement('threatLevel')->setMultiOptions(array_merge(array('' => null), $threatLevelOptions));

        $form->getElement('discoveredDate')->setValue(date('Y-m-d'));
        
        $sources = Doctrine::getTable('Source')->findAll()->toArray();
        $form->getElement('sourceId')->addMultiOptions(array('' => '--select--'));
        foreach ($sources as $source) {
            $form->getElement('sourceId')->addMultiOptions(array($source['id'] => html_entity_decode($source['name'])));
        }

        $systems = $this->_me->getOrganizationsByPrivilegeQuery('finding', 'create')
            ->leftJoin('o.System system')
            ->andWhere('system.sdlcPhase <> ?', array('disposal'))
            ->execute();
        $selectArray = $this->_view->treeToSelect($systems, 'nickname');
        $form->getElement('orgSystemId')->addMultiOptions($selectArray);

        $form->setDisplayGroupDecorators(
            array(
                new Zend_Form_Decorator_FormElements(),
                new Fisma_Zend_Form_Decorator_Finding_Create()
            )
        );
        
        $form->setElementDecorators(array(new Fisma_Zend_Form_Decorator_Finding_Create()));
        $dateElement = $form->getElement('discoveredDate');
        $dateElement->clearDecorators();
        $dateElement->addDecorator('ViewScript', array('viewScript'=>'datepicker.phtml'));
        $dateElement->addDecorator(new Fisma_Zend_Form_Decorator_Finding_Create());

        $this->_form = $form;
    }
}
