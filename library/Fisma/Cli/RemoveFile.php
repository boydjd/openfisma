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
 * Remove a file from OpenFISMA repository (without un-registering it from the database)
 *
 * @author     Duy K. Bui <duy.bui@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Cli
 */

class Fisma_Cli_RemoveFile extends Fisma_Cli_Abstract
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
            'no-warning|n' => 'Silence the warning about being not undo-able. Usefule for running inside of a script.'
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

        if (is_null($this->getOption('no-warning'))) {
            print("This action is not undo-able. Are you sure you want to continue? (y/n): ");
            $confirm = fgets(STDIN);
            if (!in_array(trim($confirm), array('y', 'yes'))) {
                $this->getLog()->info("No changes have been made to the repository.");
            }
        }

        $this->_remove();
        $this->getLog()->info("Target file successfully removed from OpenFISMA repository!");
        return true;
    }

    /**
     * Remove the file using Fisma_FileManager
     *
     * Extracted out for convinent unit testing
     *
     * @return string
     */
    protected function _remove()
    {
        $hash = $this->getOption('sha');
        $fm = new Fisma_FileManager(Fisma::getPath('fileStorage'), new finfo(FILEINFO_MIME));
        $fm->remove($hash);
    }
}
