<?php
// @codingStandardsIgnoreFile
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
 * Update YUI version 
 * 
 * @package Migration
 * @copyright (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @author Josh Boyd <joshua.boyd@endeavorsystems.com> 
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Version92 extends Doctrine_Migration_Base
{
    /**
     * Add configuration
     */
    public function up()
    {
        $config = Doctrine_Query::create()->from('Configuration')->fetchOne();
        $config->yui_version = '2.8.2';
        $config->save();
    }

    /**
     * Remove configuration 
     */
    public function down()
    {
        $config = Doctrine_Query::create()->from('Configuration')->fetchOne();
        $config->yui_version = '2.8.0r4';
        $config->save();
    }
}
