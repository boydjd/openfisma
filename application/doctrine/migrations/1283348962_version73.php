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
 * Drop the user* emailvalidate and drop the table email_validation
 * delete the email verification feature because it is rarely used. 
 * 
 * @package       Migration
 * @copyright (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @author        Mark Ma <markjtma@users.sourceforge.net>
 * @license       http://www.openfisma.org/content/license GPLv3
 */
class Version73 extends Doctrine_Migration_Base
{
    /**
     * Drop email_validation table and drop emailvalidate column in user table
     */
    public function up()
    {
        $this->dropTable('email_validation');
        $this->removeColumn('user', 'emailvalidate'); 
    }

    /**
     * This migration cannot be reversed because some data is irreversably destroyed in the up() process
     */
    public function down()
    {
        throw new Doctrine_Migration_IrreversibleMigrationException();
    }
}
