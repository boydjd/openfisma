<?php
/**
 * Copyright (c) 2013 Endeavor Systems, Inc.
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
 * @author     Duy K. Bui <duy.bui@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2013 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Migration
 */
class Application_Migration_030300_VmDashboard extends Fisma_Migration_Abstract
{
    /**
     * Migrate.
     */
    public function migrate()
    {
        $helper = $this->getHelper();
        $helper->modifyColumn(
            'vulnerability',
            'threatlevel',
            "enum('LOW','MODERATE','HIGH','CRITICAL') DEFAULT NULL",
            'threat'
        );

        $helper->exec("UPDATE `vulnerability` SET `threatlevel` = 'LOW' WHERE cvssbasescore < 3.5");
        $helper->exec(
            "UPDATE `vulnerability` SET `threatlevel` = 'MODERATE' WHERE cvssbasescore >= 3.5 AND cvssbasescore < 6.5");
        $helper->exec(
            "UPDATE `vulnerability` SET `threatlevel` = 'HIGH' WHERE cvssbasescore >= 6.5 AND cvssbasescore < 8.5");
        $helper->exec("UPDATE `vulnerability` SET `threatlevel` = 'CRITICAL' WHERE cvssbasescore >= 8.5");
    }
}
