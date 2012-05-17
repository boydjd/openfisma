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
 * Application_Migration_021700_UpdateEvaluationNames
 *
 * @uses Fisma_Migration_Abstract
 * @package Migration
 * @copyright (c) Endeavor Systems, Inc. 2012 {@link http://www.endeavorsystems.com}
 * @author Andrew Reeves <andrew.reeves@endeavorsystems.com>
 * @license http://www.openfisma.org/content/license GPLv3
 * @tickets OFJ-1696
 */
class Application_Migration_021700_UpdateEvaluationNames extends Fisma_Migration_Abstract
{
    /**
     * migrate
     *
     * @return void
     */
    public function migrate()
    {
        $updates = array(
            'ISSO Approval for Mitigation Strategy' => 'ISSO Approval of Mitigation Strategy',
            'IV&V Approval for Mitigation Strategy' => 'IV&V Approval of Mitigation Strategy',
            'ISSO Approval for Evidence'            => 'ISSO Approval of Evidence Package',
            'IV&V Approval for Evidence'            => 'IV&V Approval of Evidence Package'
        );

        foreach ($updates as $from => $to) {
            $this->getHelper()->update('evaluation', array('name' => $to), array('name' => $from));
        }
    }
}
