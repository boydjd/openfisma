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
 * A helper that lets the ACL be used in partials and views 
 * 
 * @uses Zend_View_Helper_Abstract
 * @package View_Helper 
 * @copyright (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @author Josh Boyd <joshua.boyd@endeavorsystems.com> 
 * @license http://www.openfisma.org/content/license GPLv3
 */
class View_Helper_Acl extends Zend_View_Helper_Abstract
{
    /**
     * The ACL for the user 
     * 
     * @var Fisma_Zend_Acl 
     */
    protected $_acl = null;

    /**
     * Returns the ACL 
     * 
     * @return Fisma_Zend_Acl 
     */
    public function acl()
    {
        return $this->_acl;
    }

    /**
     * setAcl 
     * 
     * @param Fisma_Zend_Acl $acl 
     * @return void
     */
    public function setAcl(Fisma_Zend_Acl $acl)
    {
        $this->_acl = $acl;
    }
}
