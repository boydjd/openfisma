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
 * Modify the LDAP schemas to standard and re-generate models.
 *
 * @author     Ryan yang <ryanyang@users.sourceforge.net>
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Migration
 */
class Version3 extends Doctrine_Migration_Base
{
    /**
     * Implement the doing changes, modify the LDAP schemas to standard and generate a new models
     * 
     * @return void
     */
    public function up()
    {
        $this->addColumn('ldap_config', 'usestarttls', 'boolean');

        $this->renameColumn('ldap_config', 'domainname', 'accountdomainname');
        $this->renameColumn('ldap_config', 'domainshort', 'accountdomainnameshort');
        $this->renameColumn('ldap_config', 'accountfilter', 'accountfilterformat');
        $this->renameColumn('ldap_config', 'accountcanonical', 'accountcanonicalform');
    }

    /**
     * Implement the undoing changes, modify the LDAP schemas to original and generate a new models
     * 
     * @return void
     */
    public function down()
    {
        $this->removeColumn('ldap_config', 'usestarttls');

        $this->renameColumn('ldap_config', 'accountdomainname', 'domainname');
        $this->renameColumn('ldap_config', 'accountdomainnameshort', 'domainshort');
        $this->renameColumn('ldap_config', 'accountfilterformat', 'accountfilter');
        $this->renameColumn('ldap_config', 'accountcanonicalform', 'accountcanonical');
    }
}
