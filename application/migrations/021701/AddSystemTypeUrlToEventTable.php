<?php
/**
 * Copyright (c) 2012 Endeavor Systems, Inc.
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
 * This migration adds the urlPath of system type to event table for showing the URL of system type in email
 *
 * @author     Ben Zheng <ben.zheng@reyosoft.com>
 * @copyright  (c) Endeavor Systems, Inc. 2012 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Migration
 */
class Application_Migration_021701_AddSystemTypeUrlToEventTable extends Fisma_Migration_Abstract
{
    /**
     * Add urlPath of system type to Event table.
     *
     * @return void
     */
    public function migrate()
    {
        $updates = array(
            'SYSTEM_TYPE_CREATED' => '/system-type/view/id/',
            'SYSTEM_TYPE_UPDATED' => '/system-type/view/id/'
        );

        foreach ($updates as $where => $to) {
            $this->getHelper()->update('event', array('urlpath' => $to), array('name' => $where));
        }
    }
}
