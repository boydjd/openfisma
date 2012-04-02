<?php
/**
 * Copyright (c) 2011 Endeavor Systems, Inc.
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
 * Fisma_Zend_Form_Manager_Organization_Converttosystem
 *
 * @uses Fisma_Zend_Form_Manager_Abstract
 * @package
 * @copyright (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @author Andrew Reeves <andrew.reeves@endeavorsystems.com>
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Fisma_Zend_Form_Manager_Organization_Converttosystem extends Fisma_Zend_Form_Manager_Abstract
{
    /**
     * prepareForm
     *
     * @return void
     */
    public function prepareForm()
    {
        $form = $this->getForm();

        $systemTable = Doctrine::getTable('System');

        $form->getElement('type')->setMultiOptions(Doctrine::getTable('SystemType')->getTypeList());
        $this->populateEnumSelect($systemTable, $form, 'sdlcPhase');
        $this->populateEnumSelect($systemTable, $form, 'confidentiality');
        $this->populateEnumSelect($systemTable, $form, 'integrity');
        $this->populateEnumSelect($systemTable, $form, 'availability');

        $this->setForm($form);
    }

    protected function populateEnumSelect(Doctrine_Table $table, Zend_Form $form, $field)
    {
        $enumValues = $table->getEnumValues($field);
        $form->getElement($field)->setMultiOptions(array_combine($enumValues, $enumValues));
    }
}
