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
 * Backup Openfisma instance and database
 * 
 * @author     Dale Frey
 * @copyright  (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Cli
 */
 
class Fisma_Cli_Backup extends Fisma_Cli_Abstract
{
    // default values while config has not yet been loaded       
    private $_myTimeStamp;   // the time stamp to name the backup folder with
    private $_appRoot;       // the root openfisma directory that contains the application, public, library, etc dirs
    private $_backupRoot;    // the directory that holds all openfisma backups
    private $_backupDir;     // the directory to place the this MySQLDump and this openfisma copy into

    /**
     * Configure the arguments accepted for this CLI program
     *
     * @return array An array containing getopt long syntax
     */
    public function getArgumentsDefinitions()
    {
        return array(
            'dir|d=s' => "Target directory to place backups.",
            'compress|c' => 'A flag that states the backup should be compressed.',
            'age|a=i' => 'The max age to keep other backups found in the backup directory'
        );
    }

    /**
     * Run the backup routine
     */
    protected function _run()
    {
    
        // Root check (based on permissions, running this script out of root may not work when copying all files)
        if (trim(strtolower(`whoami`)) !== 'root') {
            print "\nWARNING - You are running a backup script outside of root, if you receive errors " .
                "during the backup process, then;\n open a terminal\n cd to openfisma\n ". 
                "and type: sudo php -f scripts/bin/backup.php\n\n";
        }
        
        // Time changes in seconds, remember the current time
        $this->_myTimeStamp = $this->_timestamp();
        
        // config vars
        $this->_appRoot = realpath(APPLICATION_PATH . '/../');
        print "Application directory is; " . $this->_appRoot . "\n";
        
        if (is_null($this->getOption('dir'))) {
            print "Fatal Error - backup_directory is not defined." .
                "Please define state the target backup directory with the -d option.\n" . 
                "See -h for more help.\n";
            return false;
        } else {
            $this->_backupRoot = $this->getOption('dir');
            @mkdir($this->_backupRoot);                  // make sure the parent directory to _backupDir really exists
            if (file_exists($this->_backupRoot) === false) {
                print "Fatal Error - backup_directory directory pointer ($this->_backupRoot) is invalid!\n";
                return false;
            }
            $this->_backupRoot = realpath($this->_backupRoot);
        }

        // Declare $_backupDir, based on _backupRoot + _timestamp(), and create the directory
        $this->_backupDir = $this->_backupRoot . "/" . $this->_myTimeStamp . "/";
        $this->_backupDir = str_replace("//", "/", $this->_backupDir);
        
        if (!@mkdir($this->_backupDir)) {
            print "Fatal Error - Could not create backup directory ($this->_backupDir)\n";
            return false;
        }
        print "Backup directory is; $this->_backupDir\n";
        
        // Remove outdated backups
        if ($this->_pruneBackups() === false) {
            return false;
        }
        
        // Backup schema
        $this->_copySchema($backupFileSql);
        
        // copy files from the application root into the backup directory
        $this->_copyApplication();
        
        // compress backup this directory is the settings say so
        $this->_compressBackup();
        
        print "Backup completed successfully!\n";
        return true;
    }
    
    /**
     * Copies the Openfisma directory (all source code files) to the backup directory
     * 
     * @return void
     */
    private function _copyApplication()
    {
        print "Backing up application, please wait...\n";
        print "   Copying $this->_appRoot to $this->_backupDir...\n";
        $this->_recursiveCopy($this->_appRoot, $this->_backupDir, "   ");
        print "   done.\n";
    }
    
    /**
     * Compresses the backup into a .tgz file. Returns true on success and false on failure.
     * 
     * @return boolean
     */
    private function _compressBackup()
    {
        $optCompress = $this->getOption('compress');
        
        if (!is_null($optCompress) && $optCompress === true) {
            $tgzPath = $this->_backupRoot . '/' . $this->_myTimeStamp . ".tgz";
            $tgzPath = str_replace('//', '/', $tgzPath);
            print "Compressing backup into " . $tgzPath . "\n";
            chdir($this->_backupRoot);
            $s = exec("tar -zcpf " . $tgzPath . " " . $this->_myTimeStamp . "/");
            $s = exec("rm -r " . $this->_myTimeStamp . "/");
            if (!file_exists($tgzPath)) {
                print "   compress failed.\n";
                return false;
            }
            print "   done.\n";
            return true;
        }
        
    }
    
    /**
     * Copies dirsource to dirdest. Same funtionality as cp -r dirsource/ dirdest/
     * 
     * @return void
     */
    private function _recursiveCopy($dirsource, $dirdest, $debugIndent = "   ")
    {
        // bug killer - make sure there are no repeating slashes
        $dirsource = str_replace("//", "/", $dirsource);
        // bug killer - make sure there are no repeating slashes
        $dirdest = str_replace("//", "/", $dirdest);
        
        $dirHandle = @opendir($dirsource);
        if ($dirHandle === false) {
            print $debugIndent . "copy failed for dir;  $dirsource \n";
            return false;
        }
        
        $dirname = substr($dirsource, strrpos($dirsource, "/") + 1); 

        mkdir($dirdest . "/" . $dirname); 
        while ($file = readdir($dirHandle)) {
            if ($file !== "." && $file !== "..") {
                if (!is_dir($dirsource . "/" . $file)) {
                    if (!@copy($dirsource . "/" . $file, $dirdest . "/" . $dirname . "/" . $file)) {
                        print $debugIndent . "copy failed for file; " . $dirsource . "/" . $file . "\n";
                    }
                } else {
                    $dirdest1 = $dirdest . "/" . $dirname;
                    $this->_recursiveCopy($dirsource . "/" . $file, $dirdest1);
                }
            }
        }
      closedir($dirHandle);
    } 

    /**
     * Dumps a copy of the specified schema into a file inside the backup directory
     * 
     * @return void
     */
    private function _copySchema()
    {
        // Get MySql login info
        $dbConfig = new Zend_Config_Ini(Fisma::getPath('application') . '/config/database.ini');
        $db = $dbConfig->toArray();
        $db = $db['production']['db'];
        $dbUser = $db['username'];
        $dbPass = $db['password'];
        $dbSchema = $db['schema'];
        
        print "Backing up schema, please wait...\n";
        $backupFileSql = $this->_backupDir . "schema.sql";
        print "   Target file will be $backupFileSql\n";
        
        $mySqlDumpCmd =
            "mysqldump --user=" . $dbUser . 
            " --password=" . $dbPass . 
            " --add-drop-database" . 
            " --compact " . $dbSchema;
        $schema = shell_exec($mySqlDumpCmd);
        file_put_contents($backupFileSql, $schema);
        
        print "   done.\n";
    }
    
    /**
     * Removes older backups found in backup directory 
     * Returns an array of files/directories that were removed successfully
     * 
     * @return array
     */
    private function _pruneBackups()
    {
        print "Removing outdated backups...\n";
        
        // Verify prude config exists
        if (is_null($this->getOption('age'))) {
            return array();
        } else {
            $retentionPeriod = $this->getOption('age');
            print '   Backups older than ' . $retentionPeriod . " days will be removed\n";
        }
        
        // Dont prune backups?
        if ((integer) $retentionPeriod === 0) {
            return array();
        }
        
        $rtn  = array();
        $backLst = scandir($this->_backupRoot);
        
        foreach ($backLst as $oldBackupName) {
            
            // Ignore . and .. directories
            if ($oldBackupName !== '.' && $oldBackupName !== "..") {
                
                // Convert the name of the backup which should be formatted as 
                // date(YmdHis) and convert it to seconds (time());
                $oldYear = substr($oldBackupName, 0, 4);
                $oldMonth = substr($oldBackupName, 4, 2);
                $oldDay = substr($oldBackupName, 6, 2);
                $oldHour = substr($oldBackupName, 8, 2);
                $oldMin = substr($oldBackupName, 10, 2);
                $oldSec = substr($oldBackupName, 12, 2);
                $oldTime = mktime($oldHour, $oldMin, $oldSec, $oldMonth, $oldDay, $oldYear);
                
                // set timeThreshold to the time() that would be oldest acceptable age
                $secsInDay = 60 * 60 * 24;      // 86,400
                $timeThreshold = time() - ($secsInDay * (integer)$retentionPeriod);
                
                // Is $oldTime less (older) than $timeThreshold? If so, remove this backup
                if ($oldTime < $timeThreshold) {
                    
                    $oldBackupName = realpath($this->_backupRoot . '/' . $oldBackupName);
                    
                    if (is_dir($oldBackupName)) {
                        
                        print "   Removing old backup directory created on " . 
                            $oldYear . "/" . $oldMonth . "/" . $oldDay . "\n";
                        
                        $s = exec("rm -R $oldBackupName/");
                        if (file_exists("$oldBackupName/")) {
                            print "   directory deletion failed: " . realpath($oldBackupName) . "\n";
                        } else {
                            $rtn[] = realpath("$oldBackupName/");
                        }
                        
                    } else {            
                        
                        print "   Removing old backup archive created on " . 
                            $oldYear . "/" . $oldMonth . "/" . $oldDay . "\n";
                            
                        if (!unlink($oldBackupName)) {
                            print "   archive deletion failed: " . realpath($oldBackupName) . "\n";
                        } else {
                            $rtn[] = realpath($oldBackupName);
                        }
                        
                    }
                    
                }
            }
            
        }
        
        print "   done\n";
        return $rtn;
    }

    /**
     * Produces a YYYYMMDDHHMMSS _timestamp to label the backup archive with
     * 
     * @return string
     */
    private function _timestamp()
    {
        $dateNow = new Zend_Date();
        return $dateNow->toString('YYYMMddHHmmss');
    }
}