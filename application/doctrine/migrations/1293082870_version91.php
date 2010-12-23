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
 * Add soft delete behavior to vulnerability, organization, user 
 *
 * @package   Migration
 * @copyright (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @author    Josh Boyd <joshua.boyd@endeavorsystems.com>
 * @license   http://www.openfisma.org/content/license GPLv3
 */
class Version91 extends Doctrine_Migration_Base
{
    /**
     * Add deleted_at column for soft delete behavior
     */
    public function up()
    {
        $this->addColumn('vulnerability', 'deleted_at', 'timestamp', '25', array('default' => '', 'notnull' => ''));
        $this->addColumn('organization', 'deleted_at', 'timestamp', '25', array('default' => '', 'notnull' => ''));
        $this->addColumn('user', 'deleted_at', 'timestamp', '25', array('default' => '', 'notnull' => ''));
    }

    /**
     * Remove column
     */
    public function down()
    {
        $this->removeColumn('vulnerability', 'deleted_at');
        $this->removeColumn('organization', 'deleted_at');
        $this->removeColumn('user', 'deleted_at');
    }
}
