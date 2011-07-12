<?php
// @codingStandardsIgnoreFile
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
 * Add inactivity notification time configuration to Configuration model.
 *
 * @package   Migration
 * @copyright (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @author    Andrew Reeves <andrew.reeves@endeavorsystems.com>
 * @license   http://www.openfisma.org/content/license GPLv3
 */
class Version109 extends Doctrine_Migration_Base
{
    public function up()
    {
        $this->addColumn(
            'configuration',
            'session_inactivity_notice',
            'integer',
            '2',
            array( 'notblank' => '1', 'unsigned' => '1', 'default' => '0', 'comment' => 'Session timeout (seconds)')
        );
    }

    public function down()
    {
        $this->removeColumn('configuration', 'session_inactivity_notice');
    }

    public function postUp()
    {
        Doctrine_Query::create()->update("Configuration")->set("session_inactivity_notice", "session_inactivity_period * ?", 0.9)->execute();
    }
}
