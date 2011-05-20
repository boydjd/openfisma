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
 * Generate random finding objects (for load testing)
 *
 * @author     Joshua Boyd <joshua.boyd@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Cli
 */
class Fisma_Cli_GenerateFindings extends Fisma_Cli_Abstract
{
    /**
     * Configure the arguments accepted for this CLI program
     *
     * @return array An array containing getopt long syntax
     */
    public function getArgumentsDefinitions()
    {
        return array(
            'number|n=i' => "Number of finding objects to generate"
        );
    }

    /**
     * Drop the index specified on the command line, or if none is specified, drop and rebuild ALL indexes
     */
    protected function _run()
    {
        Fisma::setNotificationEnabled(false);
        Fisma::setListenerEnabled(false);

        $inMemoryConfig = new Fisma_Configuration_Array();
        $inMemoryConfig->setConfig('hash_type', 'sha1');
        $inMemoryConfig->setConfig('session_inactivity_period', '9999999');
        Fisma::setConfiguration($inMemoryConfig, true);

        $configuration = Zend_Registry::get('doctrine_config');

        $numFindings = $this->getOption('number');

        if (is_null($numFindings)) {
            throw new Fisma_Zend_Exception_User("Number is a required argument.");

            return;
        }

        $findings = array();

        // Get Organizations
        $organizationIds = Doctrine_Query::create()
                            ->select('o.id')
                            ->from('Organization o')
                            ->leftJoin('o.System s')
                            ->where("s.sdlcphase <> 'disposal' OR s.sdlcphase IS NULL")
                            ->setHydrationMode(Doctrine::HYDRATE_NONE)
                            ->execute();

        // Get sources
        $sourceIds = Doctrine_Query::create()
                        ->select('s.id')
                        ->from('Source s')
                        ->setHydrationMode(Doctrine::HYDRATE_NONE)
                        ->execute();

        // Get security controls
        $securityControlIds = Doctrine_Query::create()
                                ->select('s.id')
                                ->from('SecurityControl s')
                                ->setHydrationMode(Doctrine::HYDRATE_NONE)
                                ->execute();
                                
        // Get root user
        $rootUser = Doctrine::getTable('User')->findOneByUsername('root');
        
        // Get the evaluation ID for MSA
        $msaQuery = Doctrine_Query::create()
                    ->select('id')
                    ->from('Evaluation')
                    ->where('precedence = 0 AND approvalGroup = \'action\'');
        $msaResult = $msaQuery->execute();
        $msaEvaluation = $msaResult[0];

        // Get the evaluation ID for EA
        $eaQuery = Doctrine_Query::create()
                    ->select('id')
                    ->from('Evaluation')
                    ->where('precedence = 0 AND approvalGroup = \'evidence\'');
        $eaResult = $eaQuery->execute();
        $eaEvaluation = $eaResult[0];

        $type = array('NONE', 'CAP', 'AR', 'FP');
        $status = array('NEW', 'DRAFT', 'MSA', 'EN', 'EA', 'CLOSED');
        $threat = array('LOW', 'MODERATE', 'HIGH');
        $effectiveness = array('LOW', 'MODERATE', 'HIGH');

        $typeCount = count($type)-1;
        $statusCount = count($status)-1;
        $threatCount = count($threat)-1;
        $effectivenessCount = count($effectiveness)-1;
        $organizationIdsCount = count($organizationIds)-1;
        $sourceIdsCount = count($sourceIds)-1;
        $securityControlIdsCount = count($securityControlIds)-1;

        // Progress bar for console progress monitoring
        $generateProgressBar = $this->_getProgressBar($numFindings);
        $generateProgressBar->update(0, "Generate Findings");

        for ($i = 1; $i <= $numFindings; $i++) {
            $date = new Zend_Date();
            $date->setTimestamp(rand(time()-1e8, time()));
            $discoveredDate = $date->getDate()->toString(Fisma_Date::FORMAT_DATE);

            $date->addTimestamp(rand(0, 2e8));
            $currentEcd = $date->getDate()->toString(Fisma_Date::FORMAT_DATE);

            $finding = array();
            $finding['currentEcd'] = $currentEcd;
            $finding['type'] = $type[rand(0, $typeCount)];
            $finding['status'] = $status[rand(0, $statusCount)];
            $finding['threatLevel'] = $threat[rand(0, $threatCount)];
            $finding['countermeasuresEffectiveness'] = $effectiveness[rand(0, $effectivenessCount)];
            $finding['responsibleOrganizationId'] = $organizationIds[rand(0, $organizationIdsCount)][0];
            $finding['sourceId'] = $sourceIds[rand(0, $sourceIdsCount)][0];
            $finding['securityControlId'] = $securityControlIds[rand(0, $securityControlIdsCount)][0];
            $finding['description'] = Fisma_String::loremIpsum(rand(2, 1000));
            $finding['recommendation'] = Fisma_String::loremIpsum(rand(2, 1000));
            $finding['mitigationStrategy'] = Fisma_String::loremIpsum(rand(2, 1000));
            $finding['resourcesRequired'] = Fisma_String::loremIpsum(rand(2, 1000));
            $finding['threat'] = Fisma_String::loremIpsum(rand(2, 1000));
            $finding['countermeasures'] = Fisma_String::loremIpsum(rand(2, 1000));
            $finding['discoveredDate'] = $discoveredDate;
            $finding['ecdChangeDescription'] = Fisma_String::loremIpsum(rand(5, 10));;
            $finding['ecdLocked'] = FALSE;
            $findings[] = $finding;
            unset($finding);

            $generateProgressBar->update($i);
        }

        print "\n";

        $saveProgressBar = $this->_getProgressBar($numFindings);
        $saveProgressBar->update(0, "Save Findings");

        $currentFinding = 0;

        try {
            Doctrine_Manager::connection()->beginTransaction();

            foreach ($findings as $finding) {
                $f = new Finding();
                $f->merge($finding);
                $f->CreatedBy = $rootUser;
                $f->save();
                
                if ($f->status == 'MSA') {
                    $f->CurrentEvaluation = $msaEvaluation;
                    $f->updateDenormalizedStatus();
                    $f->save();

                    if (rand(0, 1)) {
                        $f->approve($rootUser, 'Approved by generate-findings.php script.');
                    }
                } elseif ($f->status == 'EA') {
                    // Create a sample piece of evidence
                    $evidence = new Evidence();

                    $evidence->filename = "sample-file-name.txt";
                    $evidence->Finding = $f;
                    $evidence->User = $rootUser;
                    
                    $evidence->save();
                    
                    $f->CurrentEvaluation = $eaEvaluation;
                    $f->updateDenormalizedStatus();
                    $f->save();

                    if (rand(0, 1)) {
                        $f->approve($rootUser, 'Approved by generate-findings.php script.');
                    }
                }

                $f->free();
                unset($f);

                $currentFinding++;
                $saveProgressBar->update($currentFinding);
            }

            Doctrine_Manager::connection()->commit();
        } catch (Exception $e) {
            Doctrine_Manager::connection()->rollBack();
            throw $e;
        }
    }
}
