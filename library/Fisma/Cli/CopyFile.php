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
 * Copy a file from OpenFISMA repository
 *
 * @author     Duy K. Bui <duy.bui@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Cli
 */

class Fisma_Cli_CopyFile extends Fisma_Cli_Abstract
{
    /**
     * Configure the arguments accepted for this CLI program
     *
     * @return array An array containing getopt long syntax
     */
    public function getArgumentsDefinitions()
    {
        return array(
            'sha|s=s' => 'The 40 character SHA1 hash of the file to copy out',
            'out|o=s' => 'The output path name for the file.'
        );
    }

    /**
     * Run the backup routine
     */
    protected function _run()
    {
        if (is_null($this->getOption('sha'))) {
            throw new Fisma_Zend_Exception_User ("Input hash is not defined. " .
                    "Please specify the hash key of the target input file with the -s option.\n");
        }

        if (is_null($this->getOption('out'))) {
            throw new Fisma_Zend_Exception_User ("Output file is not defined. " .
                    "Please specify the path name for the target output file with the -o option.\n");
        }

        $this->_copy();
        $this->getLog()->info("Target file successfully copied from OpenFISMA repository!");
        return true;
    }

    /**
     * Fetch the file using Fisma_FileManager
     *
     * Extracted out for convinent unit testing
     *
     * @return void
     */
    protected function _copy()
    {
        $hash = $this->getOption('sha');
        $path = $this->getOption('out');
        $fm = new Fisma_FileManager(Fisma::getPath('fileStorage'), new finfo(FILEINFO_MIME));
        $fm->copyTo($hash, $path);
    }
}
