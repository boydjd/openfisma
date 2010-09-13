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
 * Add area privileges to the IV&V role 
 * 
 * @uses Doctrine_Migration_Base
 * @package Migration 
 * @copyright (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @author Josh Boyd <joshua.boyd@endeavorsystems.com> 
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Version75 extends Doctrine_Migration_Base
{
    /**
     * Add the area privileges to the IV&V Role 
     */
    public function up()
    {
        $ivvRole = $this->_getIvvRole(); 

        $privileges = $this->_getPrivileges(); 

        foreach ($privileges as $privilege) {
            $ivvRole->Privileges[] = $privilege;
        }

        $ivvRole->save();
    }

    /**
     * Remove the area privileges from the IV&V role 
     */
    public function down()
    {
        $ivvRole = $this->_getIvvRole();

        $privileges = $this->_getPrivileges();

        foreach ($privileges as $privilege) {
            $ivvRole->unlink('Privileges', $privilege->id);
        }

        $ivvRole->save();
    }

    /**
     * Get the IV&V role object
     * 
     * @access private
     * @return Role 
     */
    private function _getIvvRole()
    {
        return Doctrine_Query::create()
               ->from('Role r')
               ->where('r.nickname = ?', 'IV&V')
               ->fetchOne();
    }

    /**
     * Get the privileges to add/remove to IV&V role 
     * 
     * @access private
     * @return Doctrine_Collection 
     */
    private function _getPrivileges()
    {
        return Doctrine_Query::create()
               ->from('Privilege p')
               ->where('p.resource = ?', 'area')
               ->andWhereIn('p.action', array('finding', 'system_inventory'))
               ->execute();
    }
}
