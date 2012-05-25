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
 * Handles security control catalogs
 *
 * @author     Andrew Reeves <andrew.reeves@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2012 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Controllers
 */
class SecurityControlCatalogController extends Fisma_Zend_Controller_Action_Object
{
    /**
     * The name of the model managed by this object controller.
     *
     * @var string
     */
    protected $_modelName = 'SecurityControlCatalog';

    /**
     * Override to return a human-friendly name
     */
    public function getSingularModelName()
    {
        return 'Security Control Catalog';
    }

    /**
     * Override to indicate that this model is not deletable.
     *
     * @return bool
     */
    protected function _isDeletable()
    {
        return false;
    }

    /**
     * Override to exclude the create button.
     *
     * @param Fisma_Doctrine_Record $record The object for which this toolbar applies, or null if not
     * @return array Array of Fisma_Yui_Form_Button
     */
    public function getToolbarButtons(Fisma_Doctrine_Record $record = null)
    {
        $buttons = parent::getToolbarButtons($record);
        unset($buttons['create']);
        return $buttons;
    }

    /**
     * Disable the createAction from FZCAO
     *
     * @GETAllowed
     */
    public function createAction()
    {
        throw new Fisma_Zend_Exception_User('Cannot create a new Security Control Catalog.');
    }
}
