<?php
/**
 * Copyright (c) 2010 Endeavor Systems, Inc.
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
 * Add relation from Incident model to Organization model
 *
 * @uses Doctrine_Migration_Base
 * @package Migration
 * @copyright (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @author Andrew Reeves <andrew.reeves@endeavorsystems.com>
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Version69 extends Doctrine_Migration_Base
{
    public function up()
    {
        $options = array(
            'notblank' => '1',
            'comment' => 'Technical support contact phone number',
            'default' => '',
            'Fisma_Doctrine_Validator_Phone' => '1',
        );
        $this->changeColumn('configuration', 'contact_phone', '15', 'string', $options);

        $options = array(
            'Fisma_Doctrine_Validator_Phone' => '1',
            'extra' => 
            array(
                'auditLog' => '1',
                'logicalName' => 'Reporter\'s Phone Number',
            ),
            'comment' => '10 digit US number with no symbols (dashes, dots, parentheses, etc.)',
        );
        $this->changeColumn('incident', 'reporterphone', '15', 'string', $options);

        $options = array(
            'Fisma_Doctrine_Validator_Phone' => '1',
            'extra' => array(
              'auditLog' => '1',
              'logicalName' => 'Reporter\'s Fax Number',
            ),
            'comment' => '10 digit US number with no symbols (dashes, dots, parentheses, etc.)',
        );
        $this->changeColumn('incident', 'reporterfax', '15', 'string', $options);

        $options = array(
            'fixed' => '1',
            'extra' => array(
                'logicalName' => 'Office Phone',
                'searchIndex' => 'keyword',
                'notify' => '1',
            ),
            'Fisma_Doctrine_Validator_Phone' => '1',
            'comment' => 'U.S. 10 digit phone number; stored without punctuation',
        );
        $this->changeColumn('user', 'phoneoffice', '15', 'string', $options);

        $options = array(
            'fixed' => '1',
            'extra' => array(
                'logicalName' => 'Mobile Phone',
                'searchIndex' => 'keyword',
                'notify' => '1',
            ),
            'Fisma_Doctrine_Validator_Phone' => '1',
            'comment' => 'U.S. 10 digit phone number, stored as 10 digits without punctuation',
        );
        $this->changeColumn('user', 'phonemobile', '15', 'string', $options);
    }

    public function down()
    {
    }
}
