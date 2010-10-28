<?php
/**
 * Copyright (c) 2010 Endeavor Systems, Inc.
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
 * This interface defines a method for Fisma_Doctrine_Table instances to override the default chunk size for indexing
 * doctrine records into the search engine.
 * 
 * @author     Mark E. Haase <mhaase@endeavorsystems.com>
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Search
 */
interface Fisma_Search_CustomChunkSize_Interface
{
    /**
     * The implementer should return the number of records for this model which should be indexed in each chunk.
     *
     * Higher numbers (100 or more) are better for small records (3-4 fields), while smaller numbers (10) are better
     * for complex models (like Finding) which have 30-40 indexed fields. Smaller numbers will result in less memory
     * usage and more frequent updates to the progress bar UI.
     * 
     * @return int
     */
    public function getIndexChunkSize();
}

