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
 * Class description
 * 
 * @author     Mark E. Haase <mhaase@endeavorystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Cli
 */
class Fisma_Cli_GenerateIncidents extends Fisma_Cli_Abstract
{
    /**
     * Some users to randomly involve in the creation of incidents
     * 
     * @var Doctrine_Collection
     */
    private $_sampleUsers;

    /**
     * Some organizations to randomly involve in the creation of incidents
     * 
     * @var Doctrine_Collection
     */
    private $_sampleOrganizations;

    /**
     * Some sub categories to randomly involve in the creation of incidents
     * 
     * @var Doctrine_Collection
     */
    private $_sampleSubCategories;
    
    /**
     * Configure the arguments accepted for this CLI program
     *
     * @return array An array containing getopt long syntax
     */
    public function getArgumentsDefinitions()
    {
        return array(
            'number|n=i' => "Number of incident objects to generate"
        );
    }

    /**
     * Create some sample incident data
     */
    protected function _run()
    {
        Fisma::setNotificationEnabled(false);
        Fisma::setListenerEnabled(false);

        $inMemoryConfig = new Fisma_Configuration_Array();
        Fisma::setConfiguration($inMemoryConfig, true);

        $configuration = Zend_Registry::get('doctrine_config');

        $numIncidents = $this->getOption('number');

        if (is_null($numIncidents)) {
            throw new Fisma_Zend_Exception_User("Number is a required argument.");

            return;
        }

        $incidents = array();

        // Get some organizations
        $this->_sampleOrganizations = Doctrine_Query::create()
                                      ->select('o.id')
                                      ->from('Organization o')
                                      ->leftJoin('o.System s')
                                      ->limit(50)
                                      ->execute();

        if (0 == count($this->_sampleOrganizations)) {
            throw new Fisma_Exception("Cannot generate sample data because the application has no organizations.");
        }

        // Get some users
        $this->_sampleUsers = Doctrine_Query::create()
                              ->from('User u')
                              ->where('u.username NOT LIKE ?', 'root')
                              ->limit(50)
                              ->execute();

        if (0 == count($this->_sampleUsers)) {
            throw new Fisma_Exception("Cannot generate sample data because the application has no users.");
        }

        // Get some subcategories
        $this->_sampleSubCategories = Doctrine_Query::create()
                                      ->from('IrSubCategory c')
                                      ->limit(50)
                                      ->execute();

        if (0 == count($this->_sampleSubCategories)) {
            throw new Fisma_Exception("Cannot generate sample data because the application has no IR categories.");
        }

        // Some enumerations to randomly pick values from
        $reporterTitle = array('Mr.', 'Mrs.', 'Miss', 'Ms.');
        $timezones = Doctrine::getTable('Incident')->getEnumValues('incidentTimezone');
        $yesNo = array('YES', 'NO');
        $mobileMediaTypes = Doctrine::getTable('Incident')->getEnumValues('piiMobileMediaType');
        $hostOs = Doctrine::getTable('Incident')->getEnumValues('hostOs');
        $status = Doctrine::getTable('Incident')->getEnumValues('status');
        $resolutions = Doctrine::getTable('Incident')->getEnumValues('resolution');

        // Progress bar for console progress monitoring
        $generateProgressBar = $this->_getProgressBar($numIncidents);
        $generateProgressBar->update(0, "Generate Incidents");

        for ($i = 1; $i <= $numIncidents; $i++) {
            $oldDate = new Zend_Date();

            // Pick a random point in the last ~3 years
            $oldDate->setTimestamp(rand(time()-1e8, time()));

            $incident = array();
            $incident['reporterTitle'] = $reporterTitle[array_rand($reporterTitle)];
            $incident['reporterFirstName'] = 'John';
            $incident['reporterLastName'] = 'Doe';
            $incident['reporterOrganization'] = 'Acme, Inc.';
            $incident['reporterAddress1'] = rand(100,9999) . ' Pennsylvania Ave.';
            $incident['reporterAddress2'] = 'Suite ' . rand (100,999);
            $incident['reporterCity'] = 'Washington';
            $incident['reporterState'] = chr(rand(65,90)) . chr(rand(65,90));
            $incident['reporterZip'] = rand(10000,99999);
            // PHP can't generate a random number greater than 2147483647, so concat a few numbers together to make a
            // phone number.
            $incident['reporterPhone'] = $this->_getRandomPhoneNumber();
            $incident['reporterFax'] = $this->_getRandomPhoneNumber();
            $incident['reporterEmail'] = 'john_doe@agency.gov';
            $incident['reporterIp'] = $this->_getRandomIpAddress();

            $incident['locationBuilding'] = "L'enfant Plaza";
            $incident['locationRoom'] = rand(100,999);

            $incident['incidentDate'] = $oldDate->getDate()->toString(Fisma_Date::FORMAT_DATE);
            $incident['incidentTime'] = $oldDate->getDate()->toString(Fisma_Date::FORMAT_TIME);
            $incident['incidentTimezone'] = $timezones[array_rand($timezones)];

            // The reportTs will be anywhere from ~0-12 days after the incident date
            $oldDate->addTimestamp(rand(0, 1e6));
            $incident['reportTs'] = $oldDate->getDate()->toString(Fisma_Date::FORMAT_DATETIME);
            $incident['reportTz'] = $timezones[array_rand($timezones)];

            $incident['additionalInfo'] = Fisma_String::loremIpsum(rand(100, 200), 'html');

            $incident['piiInvolved'] = $yesNo[array_rand($yesNo)];
            $incident['piiAdditional'] = Fisma_String::loremIpsum(rand(100, 200), 'html');
            $incident['piiMobileMedia'] = $yesNo[array_rand($yesNo)];
            $incident['piiMobileMediaType'] = $mobileMediaTypes[array_rand($mobileMediaTypes)];
            $incident['piiEncrypted'] = $yesNo[array_rand($yesNo)];
            $incident['piiAuthoritiesContacted'] = $yesNo[array_rand($yesNo)];
            $incident['piiPoliceReport'] = $yesNo[array_rand($yesNo)];
            $incident['piiIndividualsCount'] = rand(1,999999);
            $incident['piiIndividualsNotified'] = $yesNo[array_rand($yesNo)];
            $incident['piiShipment'] = $yesNo[array_rand($yesNo)];
            $incident['piiShipmentSenderContacted'] = $yesNo[array_rand($yesNo)];
            $incident['piiShipmentSenderCompany'] = ucwords(Fisma_String::loremIpsum(rand(1,3)));
            $incident['piiShipmentTimeline'] = Fisma_String::loremIpsum(rand(100, 200), 'html');
            $incident['piiShipmentTrackingNumbers'] = Fisma_String::loremIpsum(rand(100, 200), 'html');

            $incident['hostIp'] = $this->_getRandomIpAddress();
            $incident['hostName'] = 'webprod04.agency.gov';
            $incident['hostOs'] = $hostOs[array_rand($hostOs)];
            $incident['hostAdditional'] = Fisma_String::loremIpsum(rand(100, 200), 'html');

            $incident['sourceIp'] = $this->_getRandomIpAddress();
            $incident['sourceAdditional'] = Fisma_String::loremIpsum(rand(100, 200), 'html');

            $incident['actionsTaken'] = Fisma_String::loremIpsum(rand(100, 200), 'html');
            
            // Mischief. Randomly unset two fields. (Incident reports don't have required fields.)
            $nulls = array_rand($incident, 2);
            unset($incident[$nulls[0]]);
            unset($incident[$nulls[1]]);

            $incidents[] = $incident;
            unset($incident);

            $generateProgressBar->update($i);
        }

        print "\n";

        $saveProgressBar = $this->_getProgressBar($numIncidents);
        $saveProgressBar->update(0, "Save Incidents");

        $currentIncident = 0;

        try {
            Doctrine_Manager::connection()->beginTransaction();

            foreach ($incidents as $incident) {
                $i = new Incident();
                $i->merge($incident);

                // 50% are reported by a real user, 50% reported by an anonymous user
                if (rand(1,100) > 50) {
                    $i->ReportingUser = $this->_getRandomUser();                    
                }
                $i->Organization = $this->_getRandomOrganization();

                $i->save();

                // Auto approve 80% of the incidents, reject 10%, and leave 10% alone
                $action = rand(1, 100);
                if ($action <= 80) {
                    $i->open($this->_getRandomSubCategory());
                    // The first step always needs to be done, or else the incident won't look right
                    $i->completeStep("Step completed automatically by generate-incidents.php script.");
                    
                    // Complete a random number of steps on this incident
                    $stepsToComplete = rand(0, $i->Category->Workflow->Steps->count());
                    while ($stepsToComplete--) {
                        $i->completeStep("Step completed automatically by generate-incidents.php script.");
                    }
                } elseif ($action <= 90) {
                    $i->reject('Automatically rejected by generate-incidents.php script.');
                    $i->save();
                }

                $i->free();
                unset($i);

                $currentIncident++;
                $saveProgressBar->update($currentIncident);
            }

            Doctrine_Manager::connection()->commit();
        } catch (Exception $e) {
            Doctrine_Manager::connection()->rollBack();
            throw $e;
        }
    }
    
    /**
     * Return a random user object
     * 
     * @return Fisma_Record
     */
    private function _getRandomUser()
    {
        return $this->_sampleUsers[rand(0, count($this->_sampleUsers))];
    }

    /**
     * Return a random organization object
     * 
     * @return Fisma_Record
     */
    private function _getRandomOrganization()
    {
        return $this->_sampleOrganizations[rand(0, count($this->_sampleOrganizations))];
    }

    /**
     * Return a random subcategory object
     * 
     * @return Fisma_Record
     */
    private function _getRandomSubCategory()
    {
        return $this->_sampleSubCategories[rand(0, count($this->_sampleSubCategories))];
    }

    /**
     * Generate a random phone number
     * 
     * @return string
     */
    private function _getRandomPhoneNumber()
    {
        return '(' . rand(100,999) . ') ' . rand(100,999) . '-' . rand(1000,9999);
    }

    /**
     * Generate a random ipv4 address
     * 
     * @return string
     */
    private function _getRandomIpAddress()
    {
        return rand(1,255) . '.' . rand(1,255) . '.' . rand(1,255) . '.' . rand(1,255);
    }
}
