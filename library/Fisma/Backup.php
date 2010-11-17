<?php

class Fisma_Backup
{
     // default values while config has not yet been loaded
    public $config;         
    private $_myTimeStamp;   // the time stamp to name the backup folder with
    private $_appRoot;       // the root openfisma directory that contains the application, public, library, etc dirs
    private $_backupRoot;    // the directory that holds all openfisma backups
    private $_backupDir;     // the directory to place the this MySQLDump and this openfisma copy into
    
    public function doBackup()
    {
        // Root check (based on permissions, running this script out of root may not work when copying all files)
        if (trim(strtolower(`whoami`)) !== 'root') {
            $this->echoDebug(
                "\nWARNING - You are running a backup script outside of root, if you receive errors " .
                "during the backup process, then;\n open a terminal\n cd to openfisma\n ". 
                "and type: sudo php -f scripts/bin/backup.php\n\n"
            );
        }
        
        // Time changes in seconds, remember the current time
        $this->_myTimeStamp = $this->timestamp();
        
        // config vars
        $this->_appRoot = realpath(APPLICATION_PATH . '/../');
        $this->echoDebug("Application directory is; " . $this->_appRoot . "\n");
        
        if (!isset($this->config["backup"]["backup_directory"])) {
            $this->echoDebug(
                "Fatal Error - backup_directory is not defined in the configuration file! " .
                "Please define backup.backup_directory in your config file and make sure it is valid.\n"
            );
            return false;
        } else {
            $this->_backupRoot = $this->config["backup"]["backup_directory"];
            @mkdir($this->_backupRoot);                  // make sure the parent directory to _backupDir really exists
            if (file_exists($this->_backupRoot) === false) {
                $this->echoDebug("Fatal Error - backup_directory directory pointer ($this->_backupRoot) is invalid!");
                return false;
            }
            $this->_backupRoot = realpath($this->_backupRoot);
            $this->echoDebug("Backup directory is; " . $this->_backupRoot . "\n");
        }

        // Declare $_backupDir, based on _backupRoot + timestamp(), and create the directory
        $this->_backupDir = $this->_backupRoot . "/" . $this->_myTimeStamp . "/";
        $this->_backupDir = str_replace("//", "/", $this->_backupDir);
        
        if (!@mkdir($this->_backupDir)) {
            $this->echoDebug("Fatal Error - Could not create backup directory ($this->_backupDir)");
            return false;
        }
        $this->echoDebug("Backup directory is; $this->_backupDir\n");
        
        // Remove outdated backups
        if ($this->pruneBackups() === false) {
            return false;
        }
        
        // Backup schema
        $this->copySchema($backupFileSql);
        
        // copy files from the application root into the backup directory
        $this->copyApplication();
        
        // compress backup this directory is the settings say so
        $this->compressBackup();
        
        $this->echoDebug("Backup completed successfully!\n");
        return true;
    }
    
    function copyApplication()
    {
        $this->echoDebug("Backing up application, please wait...");
        $this->echoDebug("   Copying $this->_appRoot to $this->_backupDir...");
        $this->recursive_copy($this->_appRoot, $this->_backupDir, "   ");
        $this->echoDebug("   done.");
    }
    
    function compressBackup()
    {
        
        if (!isset($this->config['backup']['compress'])) {
            $this->echoDebug(
                'WARNING: backup.compress is not defined in your configuration file. This backup ' .
                'process will assume false, and not compress the backup.'
            );
        }
        
        if ($this->config['backup']['compress'] == true) {
            $tgzPath = $this->_backupRoot . '/' . $this->_myTimeStamp . ".tgz";
            $tgzPath = str_replace('//', '/', $tgzPath);
            $this->echoDebug("Compressing backup into " . $tgzPath . "\n");
            chdir($this->_backupRoot);
            $s = exec("tar -zcpf " . $tgzPath . " " . $this->_myTimeStamp . "/");
            $s = exec("rm -r " . $this->_myTimeStamp . "/");
            if (!file_exists($tgzPath)) {
                $this->echoDebug("   compress failed.");
                return false;
            }
            $this->echoDebug("   done.\n");
        }
        
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

        mkdir($dirdest . "/" . $dirname); 
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

    function copySchema()
    {
        /*
            void copySchema(string,string)
            Dumps a copy of the specified schema into a file inside the backup directory
        */
        
        $this->echoDebug("Backing up schema, please wait...");
        $backupFileSql = $this->_backupDir . "schema.sql";
        $this->echoDebug("   Target file will be $backupFileSql");
        
        $mySqlDumpCmd = "mysqldump --user=" . $this->config['db']['username'];
        $mySqlDumpCmd .= " --password=" . $this->config['db']['password'];
        $mySqlDumpCmd .= " --add-drop-database";
        $mySqlDumpCmd .= " --compact " . $this->config['db']['schema'];
        $schema = shell_exec($mySqlDumpCmd);
        file_put_contents($backupFileSql, $schema);
        
        $this->echoDebug("   done.");
    }
    
    function pruneBackups()
    {
        /*
            String[] pruneBackups(string)
            Removes old backups if they are older than $this->config['backup]['retentionPeriod'] days
            Returns an array file/directories that were removed successfully
        */
        
        $this->echoDebug("Removing outdated backups...\n");
        
        // Verify prude config exists
        if (!isset($this->config["backup"]["retentionPeriod"])) {
            $this->echoDebug(
                "Fatal Error - backup.retentionPeriod is not defined in the configuration file! " .
                "Please define retentionPeriod in your config file and make sure it is valid.\n"
            );
            return false;
        } else {
            $retentionPeriod = $this->config["backup"]["retentionPeriod"];
            $this->echoDebug('   Backups older than ' . $retentionPeriod . ' days will be removed');
        }
        
        // Dont prune backups?
        if ((integer) $retentionPeriod === 0) {
            return true;
        }
        
        $rtn  = Array();
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
                        
                        $this->echoDebug(
                            "   Removing old backup directory created on " . date("F j, Y", $oldTime) . "\n"
                        );
                        $s = exec("rm -R $oldBackupName/");
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
        
        $this->echoDebug("   done");
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
            Echoes input, only if the debug flag is marked true in the config file
            Can echo in HTML format for future use
            @returns nothing
        */
        
        // defaults
        if (!isset($this->config['backup']['debug'])) {
            $this->config['backup']['debug'] = true;
        }
        if (!isset($this->config['backup']['htmlOutput'])) {
            $this->config['backup']['htmlOutput'] = false;
        }
        
        if ($this->config['backup']['debug'] !== false) {
            if ($this->config['backup']['htmlOutput'] !== false) {
                $debugText = str_replace("\n", "<br/>", $debugText);
                $debugText = str_replace(" ", "&nbsp;", $debugText);
                echo $debugText;
            } else {
                if (strpos($debugText, "\n") === false) {
                    $debugText .= "\n";
                }                
                echo $debugText;
            }
        }
        
    }
    
}