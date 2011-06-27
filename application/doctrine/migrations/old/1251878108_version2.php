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
 * Change the Privilege resource 'finding_sources' to 'source'.
 *
 * @author     Ryan yang <ryanyang@users.sourceforge.net>
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Migration
 */
class Version2 extends Doctrine_Migration_Base
{
    /**
     * Implement the doing changes, replace "finding_sources" with "source"
     * 
     * @return void
     */
    public function up()
    {
        Doctrine_Query::create()
            ->update('Privilege p')
            ->set('p.resource', '"source"')
            ->where('p.resource = ?', 'finding_sources')
            ->execute();
    }

    /**
     * Implement the undoing changes, replace "source" with "finding_source"
     * 
     * @return void
     */
    public function down()
    {
        Doctrine_Query::create()
            ->update('Privilege p')
            ->set('p.resource', '"finding_sources"')
            ->where('p.resource = ?', 'source')
            ->execute();
    }
}
