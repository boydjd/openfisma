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
 * @package Fisma_Inject 
 * @version $Id$
 * @copyright (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @author Josh Boyd <joshua.boyd@endeavorsystems.com> 
 * @license http://www.openfisma.org/content/license GPLv2
 */
class Fisma_Inject_Factory
{
    /**
     * Create a Fisma_Inject object of the specified type with the specified data. 
     * 
     * @param string $type 
     * @param stdClass $data 
     * @return Fisma_Inject_AppDetective | Fisma_Inject_Nessus 
     */
    public static function create($type, $data)
    {
        try {
            $this->_validateType($type);
            $this->_validateData($data);

            switch($type) {
                case 'AppDetective' : 
                    return new Fisma_Inject_AppDetective($data->file, $data->networkId, $data->systemId, 
                        $data->findingSourceId);
                    break;
                case 'Nessus' : 
                    return new Fisma_Inject_Nessus($data->file, $data->networkId, $data->systemId, 
                        $data->findingSourceId);
                    break;
                default:
                    throw new Fisma_Inject_Exception($type . ' is not a valid injection plugin.');
            }
        } catch(Fisma_Inject_Exception $e) {
            throw new Fisma_Exception(
                "An exception occured while instantiating a Fisma_Inject object: $e->getMessage()"
            );
        }
    }

    /**
     * Do some basic sanity checking on the type the factory is called with.
     * 
     * @param mixed $type 
     */
    private function _validateType($type)
    {
        if (empty($type)) {
            throw new Fisma_Inject_Exception('Type cannot be empty.');
        } elseif (!is_string($type)) {
            throw new Fisma_Inject_Exception('Type must be a string.');
        }
    }

    /**
     * Do some basic sanity checking on the data the factory is called with.
     * 
     * @param mixed $data 
     */
    private function _validateData($data)
    {
        if (empty($data)) {
            throw new Fisma_Inject_Exception('Data cannot be empty.');
        } elseif (!is_object($data)) {
            throw new Fisma_Inject_Exception('Data must be an object.');
        } elseif (empty($data->file) || empty($data->networkId) || empty($data->systemId) 
            || empty($data->findingSourceId)) {
            throw new Fisma_Inject_Exception('All data fields must be populated.');
        } elseif (!is_int($data->networkId) || !is_int($data->systemId) || !is_int($data->findingSourceId)) {
            throw new Fisma_Inject_Exception('IDs must be integers.');
        } elseif (!is_string($data->file)) {
            throw new Fisma_Inject_Exception('File parameter must be a string.');
        }
    }
}
