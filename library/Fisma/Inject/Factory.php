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
 * Factory for Fisma_Inject objects 
 * 
 * @author     Josh Boyd <joshua.boyd@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Inject
 */
class Fisma_Inject_Factory
{
    /**
     * Create a Fisma_Inject object of the specified type with the specified data. 
     * 
     * @param string $type 
     * @param stdClass $data Parameters to pass to the parameter of the selected/detected injection plugin. See 
     * Fisma_Inject_Abstract for used params.
     * @return Fisma_Inject_AppDetective | Fisma_Inject_Nessus 
     */
    public static function create($type, $data)
    {
        try {
            // If $type isn't defined, then attempt to detect it.
            $type = (empty($type)) ? self::_detectType($data['filepath']) : $type;

            self::_validateType($type);

            $pluginClass = 'Fisma_Inject_' . $type;

            /**
             * Check to make sure that the class is a child of Fisma_Inject_Abstract. ReflectionClass will throw an
             * exception if the class isn't found.
             */
            $class  = new ReflectionClass($pluginClass);
            $parent = $class->getParentClass();
            
            if (!empty($parent->name) && $parent->name == 'Fisma_Inject_Abstract') { 
                return new $pluginClass($data['filepath'], $data['network']);
            }

            throw new Fisma_Inject_Exception($type . ' is not a valid injection plugin.');
        } catch (Fisma_Zend_Exception_InvalidFileFormat $e) {
            throw $e;
        } catch(Exception $e) {
            $msg = $e->getMessage();
            throw new Fisma_Zend_Exception("An exception occured while instantiating a Fisma_Inject object: $msg");
        }
    }

    /**
     * Do some basic sanity checking on the type the factory is called with.
     * 
     * @param mixed $type 
     */
    private static function _validateType($type)
    {
        if (empty($type) || !is_string($type)) {
            throw new Fisma_Zend_Exception_InvalidFileFormat('The uploaded file is not a supported file format.');
        }
    }

    /**
     * Attempt to detect the type of the file uploaded 
     * 
     * @param string $filename 
     * @return string|boolean 
     */
    private static function _detectType($filename)
    {
        $handle = fopen($filename, "rb");
        $contents = fread($handle, 128);
        fclose($handle);

        if (stristr($contents, 'NessusClientData_v2')) {
            return 'Nessus';
        } elseif (stristr($contents, 'AppDetective')) {
            return 'AppDetective';
        } elseif (stristr($contents, 'QualysGuard')) {
            return 'QualysGuard';
        } elseif (preg_match('/^<scanJob>/', $contents)) {
            return 'Retina';
        } else {
            return FALSE;
        }
    }
}
