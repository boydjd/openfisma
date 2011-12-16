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
 * Generate random users
 *
 * @author     Andrew Reeves <andrew.reeves@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Cli
 */
class Fisma_Cli_GenerateUsers extends Fisma_Cli_Abstract
{
    /**
     * Configure the arguments accepted for this CLI program
     *
     * @return array An array containing getopt long syntax
     */
    public function getArgumentsDefinitions()
    {
        return array(
            'number|n=i' => "Number of User objects to generate"
        );
    }

    /**
     * Generate the users.
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

        $numUsers = $this->getOption('number');

        if (is_null($numUsers)) {
            throw new Fisma_Zend_Exception_User("Number is a required argument.");
        }

        $users = array();

        // Get Organizations
        $organizations = Doctrine_Query::create()
                            ->select('o.id')
                            ->from('Organization o')
                            ->leftJoin('o.System s')
                            ->where("s.sdlcphase <> 'disposal' OR s.sdlcphase IS NULL")
                            ->execute();
        $roleIds = Doctrine_Query::create()
            ->select('r.id')
            ->from('Role r')
            ->setHydrationMode(Doctrine::HYDRATE_NONE)
            ->execute();

        $organizationsCount = count($organizations)-1;
        $roleIdsCount = count($roleIds)-1;

        // Include timestamp in username to make them unique
        $timestamp = time();

        // Progress bar for console progress monitoring
        $generateProgressBar = $this->_getProgressBar($numUsers);
        $generateProgressBar->update(0, "Generate Users");

        for ($i = 1; $i <= $numUsers; $i++) {
            $user = array();
            $reportingOrganizationId = -1;
            do {
                $randIndex = rand(0, $organizationsCount);
                $reportingOrganizationId = $organizations[$randIndex]->id;
                $reportingOrganizationId = (empty($organizations[$randIndex]->systemId))
                                         ? $reportingOrganizationId
                                         : -1;
            } while ($reportingOrganizationId < 0);
            $user['reportingOrganizationId'] = $reportingOrganizationId;
            $user['roleId'] = $roleIds[rand(0, $roleIdsCount)][0];
            $user['username'] = 'generated' . $timestamp . '.' . $i;
            $user['email'] = 'openfisma-default-install@googlegroups.com';
            $user['nameFirst'] = $timestamp . '.' . $i;
            $user['nameLast'] = 'Generated';
            $users[] = $user;
            unset($user);

            $generateProgressBar->update($i);
        }

        print "\n";

        $saveProgressBar = $this->_getProgressBar($numUsers);
        $saveProgressBar->update(0, "Save Users");

        $currentUser = 0;

        try {
            Doctrine_Manager::connection()->beginTransaction();

            foreach ($users as $user) {
                $u = new User();

                $u->merge($user);
                $u->save();
                $uId = $u->id;
                $u->free();
                unset($u);

                $ur = new UserRole();
                $ur->userId = $uId;
                $ur->roleId = $user['roleId'];
                foreach ($organizations as $o) {
                    $ur->Organizations[] = $o;
                }
                $ur->save();
                $ur->free();
                unset($ur);

                $currentUser++;
                $saveProgressBar->update($currentUser);
            }

            Doctrine_Manager::connection()->commit();
        } catch (Exception $e) {
            Doctrine_Manager::connection()->rollBack();
            throw $e;
        }
    }
}
