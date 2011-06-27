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
 * Add version data to Configuration 
 * 
 * @package Migration
 * @copyright (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @author Josh Boyd <joshua.boyd@endeavorsystems.com> 
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Version25 extends Doctrine_Migration_Base
{
    /**
     * Add configuration
     */
    public function up()
    {
        $config = new Configuration();
        $config->name = 'app_version';
        $config->value = '2.5.0';
        $config->save();
        $config->free();
        unset($config);

        $config = new Configuration();
        $config->name = 'yui_version';
        $config->value = '2.7.0';
        $config->save();
        $config->free();
        unset($config);
    }

    /**
     * Remove configuration 
     */
    public function down()
    {
        $app = Doctrine::getTable('Configuration')->findByName('app_version');
        $app->delete();
        $app->free();
        unset($app);
        $yui = Doctrine::getTable('Configuration')->findByName('yui_version');
        $yui->delete();
        $yui->free();
        unset($yui);
    }
}
