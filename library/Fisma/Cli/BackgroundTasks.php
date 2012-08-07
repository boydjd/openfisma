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
 * Run scheduled background tasks.
 *
 * @author     Andrew Reeves <andrew.reeves@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2012 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Cli
 */

class Fisma_Cli_BackgroundTasks extends Fisma_Cli_Abstract
{
    /**
     * Set up logging
     */
    public function __construct()
    {
        // Log all migration messages to a dedicated log
        $fileWriter = new Zend_Log_Writer_Stream(Fisma::getPath('log') . '/backgroundTasks.log');
        $fileWriter->setFormatter(new Zend_Log_Formatter_Simple("[%timestamp%] %message%\n"));

        parent::getLog()->addWriter($fileWriter);
    }

    /**
     * Run the check on lock/unlock
     */
    protected function _run()
    {
        $this->getLog()->info("Background Tasks started.");
        $configObj = Doctrine::getTable('Configuration')->createQuery()->select('backgroundTasks')->fetchOne();
        $tasks = $configObj->backgroundTasks;
        if (empty($tasks)) {
            $this->getLog()->err('No tasks configured.');
            return;
        }
        foreach ($tasks as $key => $taskInfo) {
            // first see if it needs to be run
            if (!$taskInfo['enabled']) {
                continue;
            }
            if (empty($taskInfo['lastCompletedTs'])) {
                // never executed
            } elseif ($taskInfo['unit'] == 'day') {
                // first get the last day the task successfully completed
                $date = new Zend_Date($taskInfo['lastCompletedTs']);
                // set the scheduled time
                $date->setTime($taskInfo['time']);
                // add number of days to determine when the next scheduled run is
                $date->addDay((int)$taskInfo['number']);
                // if date is in the future, we don't need to run the task yet
                if (Zend_Date::now()->isEarlier($date)) {
                    continue;
                }
            } elseif ($taskInfo['unit'] == 'minute') {
                // determine last scheduled execution
                $date = new Zend_Date($taskInfo['lastCompletedTs']);
                // clear the seconds
                $date->setSecond(0);
                // add the number of minutes before next run
                $date->addMinute((int)$taskInfo['number']);
                // if date is in the future, we don't need to run the task yet
                if (Zend_Date::now()->isEarlier($date)) {
                    continue;
                }
            } else {
                throw new Fisma_Zend_Exception(
                    "Invalid interval unit '" . $taskInfo['unit'] . "' found in task '$key'"
                );
            }

            $this->getLog()->info("Attempting to run task $key.");
            // if we get here, the task is scheduled to run, so lets attempt to get the lock
            $lockdir = realpath(APPLICATION_PATH . '/../scripts/bin/locks');
            $lockfile = $lockdir . '/' . $key . '.lock';
            $lock = fopen($lockfile, 'w+');
            if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
                // another process is running this task, so skip it
                $this->getLog()->notice("Task locked, aborting.");
                fclose($lock);
                continue;
            }
            // we now have the lock, so we can run the task
            $this->_updateTaskTs($key, 'lastRunTs');
            // run it
            $taskClass = 'Fisma_Cli_' . ucfirst($key);
            $taskObj = new $taskClass();
            $args = isset($taskInfo['arguments']) ? explode(' ', $taskInfo['arguments']) : null;
            $exit = $taskObj->run($args);
            if ($exit == 0) { // success
                $this->_updateTaskTs($key, 'lastCompletedTs');
                $this->getLog()->info("Task $key finished successfully.");
            } else {
                $this->getLog()->err("Task $key failed, will try again later.");
            }
            // release the lock
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    protected function _updateTaskTs($task, $ts)
    {
        $tbl = Doctrine::getTable('Configuration');
        $conn = $tbl->getConnection();
        $conn->beginTransaction();
        try {
            $configObj = $tbl->createQuery()->select('backgroundTasks')->fetchOne();
            $config = $configObj->backgroundTasks;
            $config[$task][$ts] = Zend_Date::now()->toString();
            $configObj->backgroundTasks = $config;
            $configObj->save();
            $conn->commit();
        } catch(Exception $e) {
            $conn->rollback();
            throw new Fisma_Zend_Exception("Error updating timestamp $ts for task $task", $e);
        }
    }
}
