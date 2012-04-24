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
 * Application_Migration_021701_MigrateSpreadsheets
 *
 * @author     Andrew Reeves <andrew.reeves@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2012 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Migration
 */
class Application_Migration_021701_MigrateSpreadsheets extends Fisma_Migration_Abstract
{
    /**
     * Main migration function called by migration script.
     *
     * @return void
     */
    public function migrate()
    {
        $this->migrateOrphans(Fisma::getPath('uploads') . '/spreadsheet/');
    }

    /**
     * Migrate orphaned files from the given path
     * @param string $path Path in which to search for orphans
     * @return void
     */
    public function migrateOrphans($path)
    {
        $fm = $this->getFileManager();

        // return if directory doesn't exist
        if (!is_dir($path)) {
            return;
        }
        // get a list of files from the directory
        $files = scandir($path);
        // remove "dot" files
        foreach ($files as $key => $value) {
            if ($value{0} == '.') {
                unset($files[$key]);
            }
        }
        $files = array_values($files);

        // if no files, nothing to do
        if (count($files) === 0) {
            return;
        }

        $query = "SELECT * FROM upload "
                 . "WHERE filehash IS NULL "
                 . "AND filename IN (" . implode(',', array_fill(0, count($files), '?')) . ")";
        $results = $this->getHelper()->query($query, $files);
        foreach ($results as $record) {
            $hash = $fm->store($path . $record->filename);
            $this->getHelper()->update(
                'upload',
                array('filehash' => $hash),
                array('id' => $record->id)
            );
            unlink($path . $record->filename);
        }
        Fisma_FileSystem::recursiveDelete($path);
    }
}
