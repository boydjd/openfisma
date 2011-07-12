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
    const SECONDS_PER_DAY = 86400;

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
            'age|a=i' => 'The max age (in days) to keep other backups found in the backup directory'
        );
    }

    /**
     * Run the backup routine
     */
    protected function _run()
    {
        
        // Time changes in seconds, remember the current time
        $this->_myTimeStamp = time();
        
        // config vars
        $this->_appRoot = realpath(APPLICATION_PATH . '/../');
        print "Application directory is " . $this->_appRoot . "\n";
        
        if (is_null($this->getOption('dir'))) {
            throw new Fisma_Zend_Exception_User ("backup_directory is not defined." .
                    "Please state the target backup directory with the -d option.\n" . 
                    "See -h for more help.");
            return false;
        } else {
        
            $this->_backupRoot = $this->getOption('dir');
            if (!is_dir($this->_backupRoot)) {
                if (!mkdir($this->_backupRoot)) {
                    throw new Fisma_Zend_Exception_User("Couldnt create backup directory: " . $this->_backupRoot);
                    return false;
                }
            }
            
            $this->_backupRoot = realpath($this->_backupRoot);
        }

        // Declare $_backupDir, based on _backupRoot + _timestamp(), and create the directory
        $this->_backupDir = $this->_backupRoot . "/" . $this->_myTimeStamp . "/";
        
        if (!mkdir($this->_backupDir)) {
            throw Fisma_Zend_Exception_User("Could not create backup directory ($this->_backupDir)");
            return false;
        }
        print "Backup directory is $this->_backupDir\n";
        
        // Remove outdated backups
        $this->_pruneBackups();
        
        // Backup schema
        $this->_copySchema();
        
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
        mkdir($this->_backupDir);
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
        
            $zipPath = realpath($this->_backupRoot) . '/' . $this->_myTimeStamp . ".zip";
            print "Compressing backup into " . $zipPath . "\n";
            chdir($this->_backupRoot);
            
            if (!$this->_createZip($this->_myTimeStamp . '/', $zipPath)) {
                print "   compression failed.\n";
                return false;
            } else {
                $this->_removeDirectory($this->_myTimeStamp . '/');
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
    private function _recursiveCopy($dirSource, $dirDest, $debugIndent = "   ")
    {
        $dirSource = realpath($dirSource);
        $dirDest = realpath($dirDest);
        
        $dirHandle = @opendir($dirSource);
        if ($dirHandle === false) {
            print $debugIndent . "copy failed for directory: $dirSource\n";
            return false;
        }
        
        $dirname = basename($dirSource); 

        mkdir($dirDest . "/" . $dirname); 
        while ($file = readdir($dirHandle)) {
            if ($file !== "." && $file !== "..") {
                if (!is_dir($dirSource . "/" . $file)) {
                    $copyFrom = $dirSource . "/" . $file;
                    $copyTo = $dirDest . "/" . $dirname . "/" . $file;
                    if (!copy($copyFrom, $copyTo)) {
                        print $debugIndent . "copy failed for $copyFrom > $copyTo \n";
                    }
                } else {
                    $dirDest1 = $dirDest . "/" . $dirname;
                    $this->_recursiveCopy($dirSource . "/" . $file, $dirDest1);
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
        global $application;
        $db = $application->getOption('db');
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
            " --compact " . $dbSchema .
            " --result-file=" . $backupFileSql;
            
        $rtnShell = shell_exec($mySqlDumpCmd);
        
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
        $toReturn  = array();
        
        print "Removing outdated backups...\n";
        
        // Are we are given an age in which older backups should be removed?
        if (is_null($this->getOption('age'))) {
            return array();
        } 
        
        $retentionPeriod = $this->getOption('age');
        print '   Backups older than ' . $retentionPeriod . " days will be removed\n";
            
        // Is this age valid?
        if (!is_numeric($retentionPeriod)) {
            throw new Fisma_Zend_Exception_User ("Invalid --age given (" . $retentionPeriod . ")");
        } else {
            if ($retentionPeriod < 1) {
                throw new Fisma_Zend_Exception_User ("The --age argument must be greater than 0");
            }
        }
        
        // set timeThreshold to the time() that would be oldest acceptable age for a previous created backup
        $timeThreshold = time() - (self::SECONDS_PER_DAY * (integer)$retentionPeriod);

        $previousBackups = scandir($this->_backupRoot);
        foreach ($previousBackups as $oldBackupName) {
            
            // Ignore . and .. directories
            if ($oldBackupName !== '.' && $oldBackupName !== "..") {
                
                // convert to full path
                $oldBackupName = realpath($this->_backupRoot . '/' . $oldBackupName);
                
                // Get the creation time of this old backup, this should be the file/directory name
                $oldTime = (integer) str_replace('.tgz', '', basename($oldBackupName));
                
                // Is $oldTime less (older) than $timeThreshold? If so, remove this backup
                if ($oldTime < $timeThreshold) {
                    
                    if (is_dir($oldBackupName)) {
                        
                        print "   Removing old backup directory: " . basename($oldBackupName) . "\n"; 
                        
                        $s = $this->_removeDirectory($oldBackupName);
                        if (file_exists("$oldBackupName")) {
                            print "   directory deletion failed: " . $oldBackupName . "\n";
                        } else {
                            $toReturn[] = $oldBackupName;
                        }
                        
                    } else {            
                        
                        print "   Removing old backup archive: " . basename($oldBackupName) . "\n";
                            
                        if (!unlink($oldBackupName)) {
                            print "   archive deletion failed: " . $oldBackupName . "\n";
                        } else {
                            $toReturn[] = $oldBackupName;
                        }
                    }
                }
            }
        }
        
        print "   done\n";
        return $toReturn;
    }

    /**
     * Create a list of all of the subdirectories and files nested within a specified directory.
     * 
     * This is similar to the POSIX "find" command.
     * 
     * @param string $targetPath The base path that you want to enumerate files from.
     * @param bool $includeDirs If true, then recurse into subdirectories.
     * @param bool $includeFiles If true, then include files contained in the directories.
     */
    private function _scanDirRecursive($targetPath, $includeDirs = true, $includeFiles = true) 
    {
        $stack = array();
        
        $targetPath = realpath($targetPath);
        $thisDir = scandir($targetPath);
        foreach ($thisDir as &$thisFile) {
            if ($thisFile !== '.' && $thisFile !== '..') {

                if (is_dir($targetPath . '/' . $thisFile)) {
                    if ($includeDirs) {
                        $stack[] = realpath($targetPath . '/' . $thisFile);
                    }
                } else {
                    if ($includeFiles) {
                        $stack[] = realpath($targetPath . '/' . $thisFile);
                    }
                }
                
                if (is_dir($targetPath . '/' . $thisFile)) {
                    $stack = array_merge(
                        $stack, 
                        $this->_scanDirRecursive($targetPath . '/' . $thisFile . '/', $includeDirs, $includeFiles)
                    );
                }
                
            }
        }
        
        return $stack;
    }

    /**
     * Recursively unlinks a directory and all of its contents
     * 
     * @param string $dirPath The path you want to unlink.
     */
    private function _removeDirectory($dirPath) 
    {

        // remove all files in tree
        $files = $this->_scanDirRecursive($dirPath, false, true);
        foreach ($files as $file) {
            unlink($file);
        }
        
        // remove all sub-directories in tree
        $dirs = $this->_scanDirRecursive($dirPath, true, false);
        $dirs = array_reverse($dirs);
        foreach ($dirs as $thisDir) {
            rmdir($thisDir);
        }
        
        // remove directory
        rmdir($dirPath);
        
    }
    
    /**
     * Create a zip file that includes all the files in a specified path
     * 
     * @param string $filesFromPath The name of the path that contains the files you want to zip.
     * @param string $destination The name of the zip file you want to create.
     */
    private function _createZip($filesFromPath, $destination) 
    {
        //create the archive
        $zip = new ZipArchive();

        if ($zip->open($destination, ZIPARCHIVE::CREATE) !== true) {
            return false;
        }

        // get file list
        $filesFromPath = realpath($filesFromPath);
        $files = $this->_scanDirRecursive($filesFromPath, false, true);

        //add the files
        foreach ($files as $file) {
            $pathWithinZip = str_replace($filesFromPath . '/', '', $file);
            $zip->addFile($file, $pathWithinZip);
        }

        $zip->close();
        return file_exists($destination);
    }
}