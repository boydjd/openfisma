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
 * Add soft delete behavior to IrSubCategory, Role, Source, VulnerabilityResolution
 *
 * @package   Migration
 * @copyright (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @author    Ben Zheng <ben.zheng@reyosoft.com>
 * @license   http://www.openfisma.org/content/license GPLv3
 */
class Version93 extends Doctrine_Migration_Base
{
    /**
     * Add deleted_at column for soft delete behavior
     */
    public function up()
    {
        $this->addColumn('ir_sub_category', 'deleted_at', 'timestamp', '25', array('default' => '', 'notnull' => ''));
        $this->addColumn('role', 'deleted_at', 'timestamp', '25', array('default' => '', 'notnull' => ''));
        $this->addColumn('source', 'deleted_at', 'timestamp', '25', array('default' => '', 'notnull' => ''));
        $this->addColumn('vulnerability_resolution', 'deleted_at', 'timestamp', '25', array(
            'default' => '',
            'notnull' => '',
            ));
    }

    /**
     * Remove column
     */
    public function down()
    {
        $this->removeColumn('ir_sub_category', 'deleted_at');
        $this->removeColumn('role', 'deleted_at');
        $this->removeColumn('source', 'deleted_at');
        $this->removeColumn('vulnerability_resolution', 'deleted_at');
    }
}
