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
 * Assets are IT hardware, software, and documentation components that comprise information systems
 * 
 * @author     Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Model
 * @version    $Id$
 */
class Asset extends BaseAsset
{
    /**
     * Declares fields stored in related records that should be indexed along with records in this table
     * 
     * This is an ugly hack. Doctrine lets us put extra attributes on columns in YAML, but not on relations.
     * So column indexing options are written in the YAML, but for now the relation indexing options have to 
     * specified within each class.
     * 
     * Each class that takes advantage of this must declare a public array called $relationIndex
     * 
     * @todo Doctrine 2.0 might provide a nicer approach for this
     */
    public $relationIndex = array(
        'Product' => array('vendor' => array('type' => 'unstored'),
                           'name' => array('type' => 'unstored'),
                           'version' => array('type' => 'unstored'))
    );
}
