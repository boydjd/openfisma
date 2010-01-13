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
 * Handles grammar for Fisma_Inject plugins 
 * 
 * @author     Josh Boyd <joshua.boyd@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Inject
 * @version    $Id$
 */
class Fisma_Inject_Grammar
{
    // contents of grammar specification 
    private $_grammar = '';
    private $_file    = 'grammar.rng';

    /**
     * Constructs a new Fisma_Inject_Grammar object. Loads the grammar into the object.
     *
     * @param string $plugin
     */
    public function __construct($plugin)
    {
        if (!empty($plugin)) {
            $this->_grammar = file_get_contents(
                realpath(dirname(__FILE__) . '/' . $plugin . '/' . $this->_file)
            );
            if (!$this->_grammar) {
                throw new Fisma_Inject_Exception('Grammar for ' . $plugin . ' could not be loaded!');
            }
        } else {
            throw new Fisma_Inject_Exception('No plugin was specified.');
        }
    }

    /**
     * Returns the contents of the grammar 
     * 
     * @return string 
     */
    public function __toString()
    {
        return $this->_grammar;
    }
}
