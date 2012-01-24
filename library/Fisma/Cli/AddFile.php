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
 * Add a file to OpenFISMA repository (without registering it to the database)
 * 
 * @author     Duy K. Bui <duy.bui@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Cli
 */
 
class Fisma_Cli_AddFile extends Fisma_Cli_Abstract
{
    /**
     * Configure the arguments accepted for this CLI program
     *
     * @return array An array containing getopt long syntax
     */
    public function getArgumentsDefinitions()
    {
        return array(
            'in|i=s' => 'The input path name for the file.'
        );
    }

    /**
     * Run the backup routine
     */
    protected function _run()
    {
        if (is_null($this->getOption('in'))) {
            throw new Fisma_Zend_Exception_User ("Input file is not defined. " .
                    "Please specify the path name for the target input file with the -i option.\n");
        }
        $u = new Upload();
        $u->fileName = pathinfo($this->getOption('in'), PATHINFO_FILENAME);
        $u->fileHash = $this->_store();
        $u->uploadIp = '127.0.0.1';
        $u->save();

        print("Target file successfully stored into OpenFISMA repository as Upload ID = {$u->id}\n" .
              "To access it with the copy-file script, please use the following SHA1 hash:\n{$u->fileHash}\n");
    }

    /**
     * Store the file using Fisma_FileManager
     *
     * Extracted out for convenient unit testing
     * 
     * @return string
     */
    protected function _store()
    {
        $path = $this->getOption('in');
        $fm = new Fisma_FileManager(Fisma::getPath('fileStorage'), new finfo(FILEINFO_MIME));
        return $fm->store($path);
    }
}
