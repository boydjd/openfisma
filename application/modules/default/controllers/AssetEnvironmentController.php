<?php
/**
 * Copyright (c) 2013 Endeavor Systems, Inc.
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
 * AssetEnvironmentController
 *
 * @uses Fisma
 * @uses _Zend_controller_Action_AbstractTagController
 * @package
 * @copyright (c) Endeavor Systems, Inc. 2013 {@link http://www.endeavorsystems.com}
 * @author Andrew Reeves <andrew.reeves@endeavorsystems.com>
 * @license http://www.openfisma.org/content/license GPLv3
 */
class AssetEnvironmentController extends Fisma_Zend_Controller_Action_AbstractTagController
{
    /**
     * _tagId
     *
     * @var string
     */
    protected $_tagId = 'asset-environment';

    /**
     * _relatedModels
     *
     * @var array
     */
    protected $_relatedModels = array(
            array(
                'model' => 'Asset',
                'column' => 'serviceTag',
                'label' => 'Asset(s)',
                'modelControllerPrefix' => '/asset'
            )
        );

    /**
     * _aclResource
     *
     * @var string
     */
    protected $_aclResource = 'Asset';

    /**
     * _aclAction
     *
     * @var string
     */
    protected $_aclAction = 'manage_environments';

    /**
     * _displayName
     *
     * @var string
     */
    protected $_displayName = "Environment";
}
