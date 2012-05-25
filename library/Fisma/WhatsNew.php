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
 * An library class for What's New feature
 *
 * @author     Duy K. Bui <duy.bui@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2012 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 */
class Fisma_WhatsNew
{
    /**
     * Verify the config files
     *
     * @param boolean $throwException   Optional. Default to false. If set to yes, the function will throw an exception
     * @return boolean
     */
    public static function checkContents($throwException = false)
    {
        $versions = Zend_Controller_Front::getInstance()->getParam('bootstrap')->getOption('versions');
        $configFile = realpath(Fisma::getPath('config')) . '/whatsnew/'
                    . substr($versions['application'], 0, -2) . '/whatsnew.yml';

        if (!file_exists($configFile)) {
            if ($throwException) {
                throw new Fisma_Zend_Exception('There is no configure file: ' . $configFile);
            }
            return false;
        }

        $contents = Doctrine_Parser_YamlSf::load($configFile);

        if (!is_array($contents) || count($contents) <= 0) {
            if ($throwException) {
                throw new Fisma_Zend_Exception_User('There is no content for ' .  substr($versions['application'], 0, -2));
            }
            return false;
        }

        return true;
    }
}
