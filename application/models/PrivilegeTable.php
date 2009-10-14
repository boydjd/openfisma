<?php
/**
 * Copyright (c) 2009 Endeavor Systems, Inc.
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
 * @author    Josh Boyd <joshua.boyd@endeavorsystems.com> 
 * @copyright (c) Endeavor Systems, Inc. 2009 (http://www.endeavorsystems.com)
 * @license   http://openfisma.org/content/license 
 * @version   $Id$
 * @package   Model
 */

 /**
  * PrivilegeTable 
  * 
  * @uses Doctrine_Table
  * @package Model 
  * @version $Id$
  * @copyright (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com})
  * @author Josh Boyd <joshua.boyd@endeavorsystems.com> 
  * @license {@link http://www.openfisma.org/content/license}
  */
 class PrivilegeTable extends Doctrine_Table
 {
    /**
     * findByResourceAndActionAndOrgSpecific 
     * 
     * @param string $resource 
     * @param string $action 
     * @param boolean $orgSpecific 
     * @access public
     * @return array 
     */
    public function findByResourceAndActionAndOrgSpecific($resource, $action, $orgSpecific) {
        return Doctrine_Query::create()
               ->from('Privilege p')
               ->where('p.resource = ?', $this->_aclResource)
               ->andWhere('p.action = ?', 'create')
               ->andWhere('p.orgSpecific = ?', true)
               ->execute(array(), Doctrine::HYDRATE_ARRAY);
    }
}
