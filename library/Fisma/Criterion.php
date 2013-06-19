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
 * A Facet Criterion
 *
 * @author     Duy K. Bui <duy.bui@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2012 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Criterion
 */
class Fisma_Criterion
{
    /**
     * Config assoc array
     */
    protected $_config;

    public function __construct($config)
    {
        $this->_config = $config;
    }

    public function __toString()
    {
        $view = Zend_Layout::getMvcInstance()->getView();
        return $view->partial(
            'criterion/abstract.phtml',
            array(
                'viewscript' => 'criterion/' . $this->_config['type'] . '.phtml',
                'config' => $this->_config
            )
        );
    }
}
