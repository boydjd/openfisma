<?php
/**
 * Copyright (c) 2011 Endeavor Systems, Inc.
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
 * Add mustresetpassword column to user table.
 *
 * @codingStandardsIgnoreFile
 *
 * @package Migration
 * @copyright (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @author Mark Ma <mark.ma@reyosoft.com>
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Version103 extends Doctrine_Migration_Base
{
    /**
    * add mustresetpassword to user table
    * 
    * @return void
    */
    public function up()
    {
        $this->addColumn('user', 'mustresetpassword', 'boolean', '1', array('default' => 0));
    }

    /**
    * remove mustresetpassword to user table
    * 
    * @return void
    */
    public function down()
    {
        $this->removeColumn('user', 'mustresetpassword');
    }
}
