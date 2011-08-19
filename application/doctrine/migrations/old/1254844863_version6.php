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
 * The Asset contains an address port field that is signed smallint. Convert to unsigned smallint.
 *
 * @author     Josh Boyd <joshua.boyd@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Migration
 */
class Version6 extends Doctrine_Migration_Base
{
    /**
     * Convert from signed to unsigned. 
     * 
     * @return void
     */
    public function up()
    {
        $this->changeColumn(
            'asset', 
            'addressPort', 
            2, 
            'integer', 
            array('unsigned' => false)
        );
    }
    
    /**
     * Convert from unsigned to signed. 
     * 
     * @return void
     */
    public function down()
    {
        $this->changeColumn(
            'asset', 
            'addressPort', 
            2, 
            'integer', 
            array('unsigned' => true)
        );
    }
}
