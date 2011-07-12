<?php
// @codingStandardsIgnoreFile
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
 * Add incident table
 * 
 * This file contains generated code... skip standards check.
 * 
 * @package Migration
 * @copyright (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @author Mark E. Haase <mhaase@endeavorsystems.com> 
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Version39 extends Doctrine_Migration_Base
{
    public function up()
    {
		$this->createTable('incident', array(
             'id' => 
             array(
              'type' => 'integer',
              'length' => 8,
              'autoincrement' => true,
              'primary' => true,
             ),
             'modifiedts' => 
             array(
              'notnull' => true,
              'type' => 'timestamp',
              'length' => 25,
             ),
             'closedts' => 
             array(
              'type' => 'timestamp',
              'length' => 25,
             ),
             'reportertitle' => 
             array(
              'type' => 'string',
              'extra' => 
              array(
              'auditLog' => true,
              'purify' => 'plaintext',
              'logicalName' => 'Reporter\'s Title',
              ),
              'length' => 255,
             ),
             'reporterfirstname' => 
             array(
              'type' => 'string',
              'extra' => 
              array(
              'auditLog' => true,
              'purify' => 'plaintext',
              'logicalName' => 'Reporter\'s First Name',
              'searchIndex' => 'unstored',
              ),
              'comment' => 'The reporter is the user who reports the incident to the organization. The report can be made directly (by logging into OpenFISMA) or can be made indirectly by reporting to an intermediary authority (such as the customer service desk) which has the privilege to enter data into OpenFISMA.
                ',
              'length' => 255,
             ),
             'reporterlastname' => 
             array(
              'type' => 'string',
              'extra' => 
              array(
              'auditLog' => true,
              'purify' => 'plaintext',
              'logicalName' => 'Reporter\'s Last Name',
              'searchIndex' => 'unstored',
              ),
              'length' => 255,
             ),
             'reporterorganization' => 
             array(
              'type' => 'string',
              'extra' => 
              array(
              'auditLog' => true,
              'purify' => 'plaintext',
              'logicalName' => 'Reporter\'s Organization',
              'searchIndex' => 'unstored',
              ),
              'length' => 255,
             ),
             'reporteraddress1' => 
             array(
              'type' => 'string',
              'extra' => 
              array(
              'auditLog' => true,
              'purify' => 'plaintext',
              'logicalName' => 'Reporter\'s Street Address Line 1',
              ),
              'length' => 255,
             ),
             'reporteraddress2' => 
             array(
              'type' => 'string',
              'extra' => 
              array(
              'auditLog' => true,
              'purify' => 'plaintext',
              'logicalName' => 'Reporter\'s Street Address Line 2',
              ),
              'length' => 255,
             ),
             'reportercity' => 
             array(
              'type' => 'string',
              'extra' => 
              array(
              'auditLog' => true,
              'purify' => 'plaintext',
              'logicalName' => 'Reporter\'s City',
              ),
              'length' => 255,
             ),
             'reporterstate' => 
             array(
              'type' => 'string',
              'extra' => 
              array(
              'auditLog' => true,
              'purify' => 'plaintext',
              'logicalName' => 'Reporter\'s State',
              ),
              'length' => 255,
             ),
             'reporterzip' => 
             array(
              'type' => 'string',
              'extra' => 
              array(
              'auditLog' => true,
              'purify' => 'plaintext',
              'logicalName' => 'Reporter\'s Zip Code',
              ),
              'length' => 10,
             ),
             'reporterphone' => 
             array(
              'type' => 'string',
              'extra' => 
              array(
              'auditLog' => true,
              'purify' => 'plaintext',
              'logicalName' => 'Reporter\'s Phone Number',
              ),
              'comment' => '10 digit US number with no symbols (dashes, dots, parentheses, etc.)',
              'length' => 15,
             ),
             'reporterfax' => 
             array(
              'type' => 'string',
              'extra' => 
              array(
              'auditLog' => true,
              'purify' => 'plaintext',
              'logicalName' => 'Reporter\'s Fax Number',
              ),
              'comment' => '10 digit US number with no symbols (dashes, dots, parentheses, etc.)',
              'length' => 15,
             ),
             'reporteremail' => 
             array(
              'type' => 'string',
              'email' => 
              array(
              'check_mx' => false,
              ),
              'extra' => 
              array(
              'auditLog' => true,
              'purify' => 'plaintext',
              'logicalName' => 'Reporter\'s E-mail Address',
              ),
              'length' => 255,
             ),
             'reporterip' => 
             array(
              'type' => 'string',
              'Fisma_Doctrine_Validator_Ip' => true,
              'extra' => 
              array(
              'auditLog' => true,
              'purify' => 'plaintext',
              'logicalName' => 'Reporter\'s IP Address',
              ),
              'comment' => 'The IP address of the client which filed this report',
              'length' => 15,
             ),
             'locationbuilding' => 
             array(
              'type' => 'string',
              'extra' => 
              array(
              'auditLog' => true,
              'purify' => 'plaintext',
              'logicalName' => 'Building',
              ),
              'comment' => 'The building in which the incident is believed to have occurred',
              'length' => 255,
             ),
             'locationroom' => 
             array(
              'type' => 'string',
              'extra' => 
              array(
              'auditLog' => true,
              'purify' => 'plaintext',
              'logicalName' => 'Room',
              ),
              'comment' => 'The room in which the incident is believed to have occurred',
              'length' => 255,
             ),
             'incidentdate' => 
             array(
              'type' => 'date',
              'extra' => 
              array(
              'auditLog' => true,
              'logicalName' => 'Incident Date',
              ),
              'comment' => 'The date on which the incident was known or believed to have occurred',
              'length' => 25,
             ),
             'incidenttime' => 
             array(
              'type' => 'time',
              'extra' => 
              array(
              'auditLog' => true,
              'logicalName' => 'Incident Time',
              ),
              'comment' => 'The time at which the incident was known or believed to have occurred',
              'length' => 25,
             ),
             'incidenttimezone' => 
             array(
              'type' => 'enum',
              'values' => 
              array(
              0 => 'AST',
              1 => 'ADT',
              2 => 'EST',
              3 => 'EDT',
              4 => 'CST',
              5 => 'CDT',
              6 => 'MST',
              7 => 'MDT',
              8 => 'PST',
              9 => 'PDT',
              10 => 'AKST',
              11 => 'AKDT',
              12 => 'HAST',
              13 => 'HADT',
              ),
              'extra' => 
              array(
              'auditLog' => true,
              'logicalName' => 'Incident Timezone',
              ),
              'comment' => 'The timezone in which the incident timestamp belongs',
              'length' => NULL,
             ),
             'reportts' => 
             array(
              'type' => 'timestamp',
              'extra' => 
              array(
              'logicalName' => 'Report Date and Time',
              ),
              'comment' => 'The time at which the incident was reported',
              'length' => 25,
             ),
             'reporttz' => 
             array(
              'type' => 'enum',
              'values' => 
              array(
              0 => 'AST',
              1 => 'ADT',
              2 => 'EST',
              3 => 'EDT',
              4 => 'CST',
              5 => 'CDT',
              6 => 'MST',
              7 => 'MDT',
              8 => 'PST',
              9 => 'PDT',
              10 => 'AKST',
              11 => 'AKDT',
              12 => 'HAST',
              13 => 'HADT',
              ),
              'extra' => 
              array(
              'logicalName' => 'Report Timezone',
              ),
              'comment' => 'The timezone in which the report timestamp belongs',
              'length' => NULL,
             ),
             'additionalinfo' => 
             array(
              'type' => 'string',
              'extra' => 
              array(
              'auditLog' => true,
              'purify' => 'html',
              'logicalName' => 'Incident Description',
              'searchIndex' => 'unstored',
              ),
              'length' => NULL,
             ),
             'piiinvolved' => 
             array(
              'type' => 'enum',
              'values' => 
              array(
              0 => 'YES',
              1 => 'NO',
              ),
              'extra' => 
              array(
              'auditLog' => true,
              'logicalName' => 'PII Involved',
              ),
              'comment' => 'Indicates whether personally identifiable information was involved',
              'length' => NULL,
             ),
             'piiadditional' => 
             array(
              'type' => 'string',
              'extra' => 
              array(
              'auditLog' => true,
              'purify' => 'html',
              'logicalName' => 'Additional PII Details',
              'searchIndex' => 'unstored',
              ),
              'comment' => 'Additional space to explain the nature of PII involved',
              'length' => NULL,
             ),
             'piimobilemedia' => 
             array(
              'type' => 'enum',
              'values' => 
              array(
              0 => 'YES',
              1 => 'NO',
              ),
              'extra' => 
              array(
              'auditLog' => true,
              'logicalName' => 'PII Stored On Mobile Media',
              ),
              'comment' => 'Was the PII stored on mobile media, such as a disc or removable drive?',
              'length' => NULL,
             ),
             'piimobilemediatype' => 
             array(
              'type' => 'enum',
              'values' => 
              array(
              0 => 'laptop',
              1 => 'disc',
              2 => 'document',
              3 => 'usb',
              4 => 'tape',
              5 => 'other',
              ),
              'extra' => 
              array(
              'auditLog' => true,
              'logicalName' => 'PII Type Of Mobile Media',
              ),
              'length' => NULL,
             ),
             'piiencrypted' => 
             array(
              'type' => 'enum',
              'values' => 
              array(
              0 => 'YES',
              1 => 'NO',
              ),
              'extra' => 
              array(
              'auditLog' => true,
              'logicalName' => 'PII Encrypted',
              ),
              'comment' => 'Was PII data encrypted on the lost media?',
              'length' => NULL,
             ),
             'piiauthoritiescontacted' => 
             array(
              'type' => 'enum',
              'values' => 
              array(
              0 => 'YES',
              1 => 'NO',
              ),
              'extra' => 
              array(
              'auditLog' => true,
              'logicalName' => 'Authorities Contacted For Loss Of PII',
              ),
              'comment' => 'Have the relevant authorities been contacted?',
              'length' => NULL,
             ),
             'piipolicereport' => 
             array(
              'type' => 'enum',
              'values' => 
              array(
              0 => 'YES',
              1 => 'NO',
              ),
              'extra' => 
              array(
              'auditLog' => true,
              'logicalName' => 'Police Report Filed For Loss Of PII',
              ),
              'comment' => 'Has a police report been filed?',
              'length' => NULL,
             ),
             'piiindividualscount' => 
             array(
              'type' => 'int',
              'extra' => 
              array(
              'auditLog' => true,
              'logicalName' => 'Number Of Individuals Affected By Loss Of PII',
              ),
              'comment' => 'The number of individuals potentially compromised by this incident\\\'s loss of PII',
              'length' => 10,
             ),
             'piiindividualsnotified' => 
             array(
              'type' => 'enum',
              'values' => 
              array(
              0 => 'YES',
              1 => 'NO',
              ),
              'extra' => 
              array(
              'auditLog' => true,
              'logicalName' => 'Inviduals Affected By PII Have Been Notified',
              ),
              'comment' => 'Have the affected individuals been contacted?',
              'length' => NULL,
             ),
             'piishipment' => 
             array(
              'type' => 'enum',
              'values' => 
              array(
              0 => 'YES',
              1 => 'NO',
              ),
              'extra' => 
              array(
              'auditLog' => true,
              'logicalName' => 'PII Lost During Shipment',
              ),
              'comment' => 'Was the loss of PII due to a shipment?',
              'length' => NULL,
             ),
             'piishipmentsendercontacted' => 
             array(
              'type' => 'enum',
              'values' => 
              array(
              0 => 'YES',
              1 => 'NO',
              ),
              'extra' => 
              array(
              'auditLog' => true,
              'logicalName' => 'Shipping Company Contacted',
              ),
              'comment' => 'Contact information for the company responsible for shipping the PII',
              'length' => NULL,
             ),
             'piishipmentsendercompany' => 
             array(
              'type' => 'string',
              'extra' => 
              array(
              'auditLog' => true,
              'purify' => 'plaintext',
              'logicalName' => 'Name Of Shipping Company',
              ),
              'length' => 255,
             ),
             'piishipmenttimeline' => 
             array(
              'type' => 'string',
              'extra' => 
              array(
              'auditLog' => true,
              'purify' => 'html',
              'logicalName' => 'Description Of Shipment Timeline',
              ),
              'length' => NULL,
             ),
             'piishipmenttrackingnumbers' => 
             array(
              'type' => 'string',
              'extra' => 
              array(
              'auditLog' => true,
              'purify' => 'html',
              'logicalName' => 'Shipment Tracking Numbers',
              ),
              'length' => NULL,
             ),
             'hostip' => 
             array(
              'type' => 'string',
              'Fisma_Doctrine_Validator_Ip' => true,
              'extra' => 
              array(
              'auditLog' => true,
              'purify' => 'plaintext',
              'logicalName' => 'Host IP Address',
              ),
              'comment' => 'The IP address of the affected host',
              'length' => 15,
             ),
             'hostname' => 
             array(
              'type' => 'string',
              'extra' => 
              array(
              'auditLog' => true,
              'purify' => 'plaintext',
              'logicalName' => 'Host Name',
              ),
              'length' => 255,
             ),
             'hostos' => 
             array(
              'type' => 'enum',
              'values' => 
              array(
              0 => 'win7',
              1 => 'vista',
              2 => 'xp',
              3 => 'macos',
              4 => 'linux',
              5 => 'unix',
              ),
              'extra' => 
              array(
              'auditLog' => true,
              'logicalName' => 'Host Operating System',
              ),
              'length' => NULL,
             ),
             'hostadditional' => 
             array(
              'type' => 'string',
              'extra' => 
              array(
              'auditLog' => true,
              'purify' => 'html',
              'logicalName' => 'Additional Host Details',
              ),
              'length' => NULL,
             ),
             'sourceip' => 
             array(
              'type' => 'string',
              'Fisma_Doctrine_Validator_Ip' => true,
              'extra' => 
              array(
              'auditLog' => true,
              'purify' => 'plaintext',
              'logicalName' => 'Source IP Address',
              ),
              'comment' => 'The IP address from which the incident is believed to have originated',
              'length' => 15,
             ),
             'sourceadditional' => 
             array(
              'type' => 'string',
              'extra' => 
              array(
              'auditLog' => true,
              'purify' => 'html',
              'logicalName' => 'Additional Details About Incident Source',
              ),
              'comment' => 'Additional description of the source or origin of the incident',
              'length' => NULL,
             ),
             'actionstaken' => 
             array(
              'type' => 'string',
              'extra' => 
              array(
              'auditLog' => true,
              'purify' => 'html',
              'logicalName' => 'Actions That Were Taken Prior To Incident Report',
              'searchIndex' => 'unstored',
              ),
              'comment' => 'What actions were taken prior to reporting the incident?',
              'length' => NULL,
             ),
             'status' => 
             array(
              'type' => 'enum',
              'values' => 
              array(
              0 => 'new',
              1 => 'open',
              2 => 'closed',
              ),
              'extra' => 
              array(
              'auditLog' => true,
              'logicalName' => 'Status',
              'searchIndex' => 'unstored',
              ),
              'length' => NULL,
             ),
             'resolution' => 
             array(
              'type' => 'enum',
              'values' => 
              array(
              0 => 'rejected',
              1 => 'resolved',
              ),
              'extra' => 
              array(
              'auditLog' => true,
              'logicalName' => 'Resolution',
              'searchIndex' => 'unstored',
              ),
              'length' => NULL,
             ),
             'currentworkflowstepid' => 
             array(
              'type' => 'integer',
              'comment' => 'Foreign key to the workflow for this incident',
              'length' => 8,
             ),
             'reportinguserid' => 
             array(
              'type' => 'integer',
              'comment' => 'Foreign key to the user who reported this incident',
              'length' => 8,
             ),
             'categoryid' => 
             array(
              'type' => 'integer',
              'comment' => 'Foreign key to the IR sub category in which this incident belongs',
              'length' => 8,
             ),
             'islocked' => 
             array(
              'type' => 'boolean',
              'length' => 25,
              'notnull' => true,
              'default' => 0,
             ),
             ), array(
             'indexes' => 
             array(
             ),
             'primary' => 
             array(
              0 => 'id',
             ),
             ));
    }

    public function down()
    {
		$this->dropTable('incident');
    }
}
