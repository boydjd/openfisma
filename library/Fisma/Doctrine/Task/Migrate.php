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
 * Contain Doctrine_Task functions
 * 
 * @uses Doctrine_Task
 * @package Fisma
 * @subpackage Fisma_Doctrine_Task
 * @copyright (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @author Mark Ma <mark.ma@reyosoft.com> 
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Fisma_Doctrine_Task_Migrate extends Doctrine_Task
{
    public $description = 'Migrate database to latest version or the specified version',
           $requiredArguments = array('migrations_path' => 'Specify path to your migrations directory.'),
           $optionalArguments = array('version' => 'Version to migrate to. If you do not specify, 
                                       the db will be migrated from the current version to the latest.'); 

    public function execute()
    {
        $migrationPath = $this->getArgument('migrations_path');
 
        $migration = new Doctrine_Migration($migrationPath);
        $from = $migration->getCurrentVersion();

        if ($this->getArgument('version')) {
            $to = $this->getArgument('version');
        } else {
            $to = $migration->getLatestVersion();
        } 

        if ($from == $to) {
            throw new Doctrine_Migration_Exception('Already at version #' . $to);
        }

        if ($from < $to) {
            $this->notify('Planning migration from #' . $from .' to #'. $to);
        }

        $migrationClasses = $migration->getMigrationClasses();

        if ($to < $from) {
            for ($i = (int)$from - 1; $i >= (int)$to; $i--) {
                $this->notify('Migrate down #' . ($i + 1));
                $version = $migration->migrate($i);
            }
        } else {
            for ($i = (int)$from + 1; $i <= (int)$to; $i++) {
                $this->notify('Migrate up #' . $i);
                $migration->migrate($i);
            }
        }
    }
}
