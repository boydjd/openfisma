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
 * Correct a spelling mistake in the NIST SP 800-53 Rev 1 SA-02 Supplemental Guidance
 *
 * @author     Andrew Reeves <andrew.reeves@eneavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2012 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Migration
 */
class Application_Migration_021800_Rev1Sa02 extends Fisma_Migration_Abstract
{
    /**
     * Migrate
     */
    public function migrate()
    {
        $newValue = "The organization includes the determination of security requirements for the information system "
                  . "in  mission/business case planning and establishes a discrete line item for information system "
                  . "security in  the organization's programming and budgeting documentation. NIST Special "
                  . "Publication 800-65 provides  guidance on integrating security into the capital planning and "
                  . "investment control process.";

        $this->getHelper()->update('security_control', array('supplementalguidance' => $newValue), array('id' => 290));
    }
}
