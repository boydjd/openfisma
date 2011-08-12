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
 * Add a configuration to control Smtp supports TLS.
 * 
 * @author     Ben Zheng <benzheng@users.sourceforge.net>
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Migration
 */
class Version8 extends Doctrine_Migration_Base
{
    /**
     * Insert new configuration
     * 
     * @return void
     */
    public function up()
    {
        $config = new Configuration();
        $config->name        = 'smtp_tls';
        $config->value       = 0;
        $config->description = 'Use Transport Layer Security (TLS)';
        $config->save();
    }

    /**
     * Remove new configuration
     * 
     * @return void
     */
    public function down()
    {
        $query = Doctrine_Query::create()
               ->from('Configuration c')
               ->where('c.name = ?', 'smtp_tls'); 
        $config = $query->fetchOne();
        if ($config) {
            $config->delete();
        }
    }
}
