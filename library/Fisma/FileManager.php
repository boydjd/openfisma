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
 * Fisma_FileManager 
 * 
 * @package Fisma
 * @copyright (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @author Andrew Reeves <andrew.reeves@endeavorsystems.com> 
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Fisma_FileManager
{
    /**
     * Base directory of storage.
     */
    private $_baseDir;

    /**
     * finfo instance
     * 
     * @var finfo
     */
    private $_finfo;

    /**
     * @param String $baseDir Base storage directory
     */
    public function __construct($baseDir, finfo $finfo)
    {
        $this->_baseDir = $baseDir;
        $this->_finfo = $finfo;
    }

    /**
     * @param string $filePath Path to temporary location of file to be stored.
     * @return string SHA-1 Hash of stored file
     */
    public function store($filePath)
    {
        $sha1 = $this->_sha1File($filePath);
        if (!$sha1) {
            throw new Fisma_FileManager_Exception('Unable to compute SHA! sum of source file: ' . $filePath);
        }
        $path = substr($sha1, 0, 2);
        $name = substr($sha1, 2);
        $destDir = $this->_baseDir . DIRECTORY_SEPARATOR . $path;
        $dest = $destDir . DIRECTORY_SEPARATOR . $name;
        if (!$this->_fileExists($destDir)) {
            $mkdirOk = $this->_mkdir($destDir);
            if (!$mkdirOk) {
                throw new Fisma_FileManager_Exception('Unable to create directory: ' . $destDir);
            }
        }
        if (!$this->_fileExists($dest)) {
            $copyOk = $this->_copy($filePath, $dest);
            if (!$copyOk) {
                throw new Fisma_FileManager_Exception('Unable to copy to storage location: ' . $dest);
            }
        }
        return $sha1;
    }

    /**
     * @param $hash Hash of desired file.
     * @param $destination Location to save requested file.
     * @return void
     */
    public function copyTo($hash, $destination)
    {
        $source = $this->_hashPath($hash);
        if (!$this->_fileExists($source)) {
            throw new Fisma_FileManager_Exception(
                'Unable to copy file from storage, requested file does not exist: ' . $source
            );
        }
        $copyOk = $this->_copy($source, $destination);
        if (!$copyOk) {
            throw new Fisma_FileManager_Exception('Unable to copy file to destination: ' . $destination);
        }
    }

    /**
     * Determine the MIME Type of a file.
     *
     * Currently employing the FileInfo library provided by _finfo
     * 
     * @param $hash Hash of the file to determine MIME type for
     * 
     * @return string
     */
    public function getMimeType($hash)
    {
        $source = $this->_hashPath($hash);
        if (!$this->_fileExists($source)) {
            throw new Fisma_FileManager_Exception('Cannot determine MIME, requested file does not exist: ' . $source);
        }

        return $this->_finfo->file($source);
    }

    /**
     * @param string $hash Hash of file to stream
     * @return void
     */
    public function stream($hash)
    {
        $source = $this->_hashPath($hash);
        if (!$this->_fileExists($source)) {
            throw new Fisma_FileManager_Exception('Cannot stream, requested file does not exist: ' . $source);
        }

        $this->_readfile($source);
    }

    /**
     * @param string $hash Hash of file
     * @return int
     */
    public function getFileSize($hash)
    {
        $source = $this->_hashPath($hash);
        if (!$this->_fileExists($source)) {
            throw new Fisma_FileManager_Exception('Cannot stream, requested file does not exist: ' . $source);
        }

        return $this->_filesize($source);
    }

    /**
     * Helper method to get a full path in storage for a SHA1 hash.
     *
     * @param string $hash SHA1 Hash for which we're providing a path
     * @return string Path string
     */
    protected function _hashPath($hash)
    {
        $path = substr($hash, 0, 2);
        $name = substr($hash, 2);
        return implode(array($this->_baseDir, $path, $name), DIRECTORY_SEPARATOR);
    }

    /**
     * Wrapping function for file-related function sha1_file()
     * 
     * @param string $filename 
     * @return string
     */
    protected function _sha1File($filename)
    {
        return sha1_file($filename);
    }

    /**
     * Wrapper for file-related function file_exists()
     * 
     * @param string $filename 
     * @return bool
     */
    protected function _fileExists($filename)
    {
        return file_exists($filename);
    }

    /**
     * Wrapper for file-related function mkdir()
     * 
     * @param string $directory 
     * @return bool
     */
    protected function _mkdir($directory)
    {
        return mkdir($directory, 0777, true);
    }

    /**
     * Wrapper for file-related function copy()
     * 
     * @param string $source 
     * @param string $dest 
     * @return bool
     */
    protected function _copy($source, $dest)
    {
        return copy($source, $dest);
    }

    /**
     * Wrapper for file-related function readfile()
     * 
     * @param string $filename 
     * @return int
     */
    protected function _readfile($filename)
    {
        return readfile($filename);
    }

    /**
     * Wrapper for file-related function filesize()
     * 
     * @param string $filename 
     * @return int
     */
    protected function _filesize($filename)
    {
        return filesize($filename);
    }
}
