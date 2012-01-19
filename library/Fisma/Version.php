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
 * Represents a version number for OpenFISMA
 *
 * @author     Mark E. Haase <mhaase@endeavorystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Version
 */
class Fisma_Version
{
    /**
     * The major version, e.g. 2 in 2.17.0.
     *
     * @param int
     */
    private $_majorVersion;

    /**
     * The minor version, e.g. 17 in 2.17.0.
     *
     * @param int
     */
    private $_minorVersion;

    /**
     * The tag number, e.g. 0 in 2.17.0.
     *
     * @param int
     */
    private $_tagNumber;

    /**
     * Create a new version object
     *
     * @param int $majorVersion
     * @param int $minorVersion
     * @param int $tagNumber
     */
    public function __construct($majorVersion, $minorVersion, $tagNumber)
    {
        if (is_numeric($majorVersion) && is_numeric($minorVersion) && is_numeric($tagNumber)) {
            $this->_majorVersion = (int)$majorVersion;
            $this->_minorVersion = (int)$minorVersion;
            $this->_tagNumber = (int)$tagNumber;
        } else {
            throw new Fisma_Zend_Exception("Invalid version parameters ($majorVersion, $minorVersion, $tagNumber).");
        }
    }

    /**
     * Get the major version.
     *
     * @return int
     */
    public function getMajorVersion()
    {
        return $this->_majorVersion;
    }

    /**
     * Get the minor version.
     *
     * @return int
     */
    public function getMinorVersion()
    {
        return $this->_minorVersion;
    }

    /**
     * Get the tag number.
     *
     * @return int
     */
    public function getTagNumber()
    {
        return $this->_tagNumber;
    }

    /**
     * Return the version as a dotted string, e.g. "2.17.0";
     *
     * @return string
     */
    public function getDottedString()
    {
        return "{$this->_majorVersion}.{$this->_minorVersion}.{$this->_tagNumber}";
    }

    /**
     * Return the version as a sig digit string (with left padding if necessary), e.g. "021700".
     *
     * @return string
     */
    public function getPaddedString()
    {
        $version = str_pad($this->_majorVersion, 2, '0', STR_PAD_LEFT)
                 . str_pad($this->_minorVersion, 2, '0', STR_PAD_LEFT)
                 . str_pad($this->_tagNumber, 2, '0', STR_PAD_LEFT);

        return $version;
    }

    /**
     * Create a version object from a dotted version string like "2.17.0".
     *
     * @param string $versionString
     */
    static function createVersionFromDottedString($versionString)
    {
        $parts = explode('.', $versionString);

        if (count($parts) != 3) {
            throw new Fisma_Zend_Exception("Invalid version string ($versionString)");
        }

        return new self($parts[0], $parts[1], $parts[2]);
    }

    /**
     * Create a version object from a six digit string, like "021700" to represent 2.17.0.
     *
     * @param string $versionString
     */
    static function createVersionFromPaddedString($versionString)
    {
        if (strlen($versionString) != 6) {
            throw new Fisma_Zend_Exception("Invalid version string ($versionString)");
        }

        return new self(substr($versionString, 0, 2), substr($versionString, 2, 2), substr($versionString, 4, 2));
    }
}
