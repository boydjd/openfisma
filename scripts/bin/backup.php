<?php
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
* backup.php
*
* This is a script to take a backup of an OpenFISMA application
* instance. The script makes a copy of all source code files and also
* produces a schema dump. The backup is tar'ed and gzip'ed.
* 
* Before running this script, make sure to edit the
* backup-restore.cfg file to specify the proper database access
* properties.
*
* The script is designed to run in a POSIX environment, but may run
* under windows if a compatible mysqldump and tar executable exists
* in the path.
 * 
 * @author     Dale Frey
 * @copyright  (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package  
 * @version    $Id$
 */

$backObj = new backup();
$backObj->doBackup();

class backup
{
    
     // default values while config has not yet been loaded
    public $config = Array('debug' => true, 'htmlOutput' => false);
        
    function doBackup()
    {
        $myTimeStamp = $this->timestamp(); // Time will change, set a current one
        
        // Read & parse the configuration file
        $configPath = dirname(__FILE__) . "/backup-restore.cfg";
        $this->config = $this->getConfig($configPath);
        // verify there is a config file exists
        if ($this->config === false) {
            $this->echoDebug("Fatal Error - No configuration file found! (Create $configPath.)");
            return false;
        }

        // verify and validate config vars
        $appRoot = $this->config["appRoot"];
        $this->echoDebug("Application directory is; " . $appRoot . "\n");
        if (!file_exists($appRoot)) {
            $this->echoDebug(
                "Fatal Error - Application directory ($appRoot) does not exist! Please define appRoot in your " .
                "config file and make sure it is valid.\n"
            );
            return false;
        }
        
        if (!isset($this->config["backupRoot"])) {
            $this->echoDebug(
                "Fatal Error - backupRoot is not defined in the configuration file! " .
                "Please define appRoot in your config file and make sure it is valid.\n"
            );
            return false;
        }
        $backupRoot = $this->config["backupRoot"];
        
        // Declare $backupDir, based on backupRoot + timestamp(), and create the directory
        $backupDir = $backupRoot . "/" . $myTimeStamp . "/";
        $backupDir = str_replace("//", "/", $backupDir);
        @mkdir($backupRoot);        // make sure the parent directory to backupDir really exists
        if (!@mkdir($backupDir)) {
            $this->echoDebug("Fatal Error - Could not create backup directory ($backupDir)");
            return false;
        }
        $this->echoDebug("Backup directory is; $backupDir\n");
        
        // Remove outdated backups
        $this->echoDebug("Removing outdated backups...\n");
        $this->pruneBackups($backupRoot);
        $this->echoDebug("   done.\n");
        
        // Backup schema
        $this->echoDebug("Backing up schema, please wait...\n");
        $backupFileSql = $backupDir . "schema.sql";
        $this->echoDebug("   Target file will be $backupFileSql\n");
        $this->copySchema($backupFileSql);
        $this->echoDebug("   done.\n");
        
        // copy files from the application root into the backup directory
        $this->echoDebug("Backing up application, please wait...\n");
        $this->recursive_copy($appRoot, $backupDir . "/app/", "   ");
        $this->echoDebug("   done.\n");
        
        if ($this->config['compress'] == true) {
            $tgzPath = $backupRoot . $myTimeStamp . ".tgz";
            $this->echoDebug("Compressing backup into " . $tgzPath . "\n");
            chdir($backupRoot);
            $s = exec("tar -zcpf " . $tgzPath . " " . $myTimeStamp . "/");
            $s = exec("rm -r " . $myTimeStamp . "/");
            if (!file_exists($tgzPath)) {
                $this->echoDebug("   compress failed.");
                return false;
            }
            $this->echoDebug("   done.\n");
        }
        
        $this->echoDebug("Backup completed successfully!\n");
        return true;
    }

    function recursive_copy($dirsource, $dirdest, $debugIndent = "   ")
    {
        
        // bug killer - make sure there are no repeating slashes
        $dirsource = str_replace("//", "/", $dirsource);
        // bug killer - make sure there are no repeating slashes
        $dirdest = str_replace("//", "/", $dirdest);
        
        $dirHandle = @opendir($dirsource);
        if ($dirHandle === false) {
            $this->echoDebug($debugIndent . "copy failed for dir;  $dirsource \n");
            return false;
        }
        
        $dirname = substr($dirsource, strrpos($dirsource, "/") + 1); 

        @mkdir($dirdest . "/" . $dirname); 
        while ($file = readdir($dirHandle)) {
            if ($file !== "." && $file !== "..") {
                if (!is_dir($dirsource . "/" . $file)) {
                    if (!@copy($dirsource . "/" . $file, $dirdest . "/" . $dirname . "/" . $file)) {
                        $this->echoDebug($debugIndent . "copy failed for file; " . $dirsource . "/" . $file . "\n");
                    }
                } else {
                    $dirdest1 = $dirdest . "/" . $dirname;
                    $this->recursive_copy($dirsource . "/" . $file, $dirdest1);
                }
            }
        }
      closedir($dirHandle); 
      return true;
    } 

    function copySchema($backupFile)
    {
        /*
            void copySchema(string,string)
            Dumps a copy of the specified schema into a file inside the backup directory
        */
        
        $mySqlDumpCmd = "mysqldump --user=" . $this->config["dbUser"];
        $mySqlDumpCmd .= " --password=" . $this->config["dbPassword"];
        $mySqlDumpCmd .= " --add-drop-database";
        $mySqlDumpCmd .= " --compact " . $this->config["dbSchema"];
        $schema = shell_exec($mySqlDumpCmd);
        file_put_contents($backupFile, $schema);
        
    }
    
    function pruneBackups($backupDir)
    {
        /*
            String[] pruneBackups(string)
            Removes old backups if they are older than $this->config['retentionPeriod'] days
            Returns an array file/directories that were removed successfully
        */
        
        // Dont prune backups?
        if ((integer) $this->config['retentionPeriod'] === 0) {
            return "";
        }
        
        $rtn  = Array();
        $backLst = scandir($backupDir);
        
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
                $timeThreshold = time() - ($secsInDay * (integer)$this->config['retentionPeriod']);
                
                // Is $oldTime less (older) than $timeThreshold? If so, remove this backup
                if ($oldTime < $timeThreshold) {
                    
                    $oldBackupName = realpath($backupDir . $oldBackupName);
                    
                    if (is_dir($oldBackupName)) {
                        
                        $this->echoDebug(
                            "   Removing old backup directory created on " . date("F j, Y", $oldTime) . "\n"
                        );
                        $s = exec("rm -r $oldBackupName/");
                        if (file_exists("$oldBackupName/")) {
                            $this->echoDebug("   directory deletion failed: " . realpath($oldBackupName) . "\n");
                        } else {
                            $rtn[] = realpath("$oldBackupName/");
                        }
                        
                    } else {
                        
                        $this->echoDebug(
                            "   Removing old backup archive created on " . date("F j, Y", $oldTime) . "\n"
                        );
                        if (!unlink($oldBackupName)) {
                            $this->echoDebug("   archive deletion failed: " . realpath($oldBackupName) . "\n");
                        } else {
                            $rtn[] = realpath($oldBackupName);
                        }
                        
                    }
                    
                }
            }
            
        }
    }

    function getConfig($configPath)
    {
        /*
            String[] getConfig(string)
            Reads the contents of the file-path inputted and returns an array
            Returns false if the given input path does not exist
        */
        
        $rtnArray = Array('debug' => true, 'htmlOutput' => false);  // defaults
        
        // verify there is a config file exists
        if (!file_exists($configPath)) {
            return false;
        }
        
        // load config file
        $fileLines = file($configPath);
        // take out CRs, or LFs left behind which file() sometimes does
        $fileLines = str_replace(chr(10), "", $fileLines);
        // take out CRs, or LFs left behind which file() sometimes does
        $fileLines = str_replace(chr(13), "", $fileLines);
        
        foreach ($fileLines as $thisLine) {                         // for each line in the file
            
            if (substr($thisLine, 0, 1) !== "#") {                 // is the line not start with # (comment)
                if (strpos($thisLine, "=") !== false) {            // does this line have a; something = something?
                    
                    $thisLineParts = explode("=", $thisLine);   // divide the line by "=" as the delimiter
                    $readField = trim($thisLineParts[0]);
                    $readValue = trim($thisLineParts[1]);
                    
                    if (strtolower($readValue) === 'true') {    // convert string to bool
                        $readValue = true;
                    }
                    if (strtolower($readValue) === 'false') {   // convert string to bool
                        $readValue = false;
                    }
                    
                    // note the read value as an element in the return array with the key of read-field
                    $rtnArray[$readField] = $readValue;
                    
                }
            }

        }

        return $rtnArray;
    }
        
    function timestamp()
    {
        /**
            String timestamp()
            Produces a YYYYMMDDHHMMSS timestamp to label the backup archive with
            @returns string
        */
    
        return date("YmdHis");
    }
    
    function echoDebug($debugText)
    {
        /**
            void echoDebugInfo()
            Uses $this->echoDebug() only if the degub flag is marked true in the config file
            Can echo in HTML format for future use
            @returns nothing
        */
        
        if ($this->config['debug'] !== false) {
            if ($this->config['htmlOutput'] !== false) {
                $debugText = str_replace("\n", "<br/>", $debugText);
                $debugText = str_replace(" ", "&nbsp;", $debugText);
                echo $debugText;
            } else {
                echo $debugText;
            }
        }
        
    }
    
}