/**
 * Copyright (c) 2008 Endeavor Systems, Inc.
 *
 * This file is part of OpenFISMA.
 *
 * OpenFISMA is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * OpenFISMA is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with OpenFISMA.  If not, see {@link http://www.gnu.org/licenses/}.
 *
 * @fileoverview Functions related to the search engine and search UI
 *
 * @author    Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @license   http://www.openfisma.org/content/license
 * @package   Fisma
 */

/**
 * Constructor
 * 
 * @param searchableFields Names and data types for searchable fields
 */
Fisma.Search.Panel = function (searchableFields) {

    if (0 == searchableFields.length) {
        throw "Field array cannot be empty";
    }
    
    // Sort fields by name
    searchableFields.sort(
        function (a, b) {
            if (a.name < b.name) {
                return -1;
            } else if (a.name > b.name) {
                return 1;
            } else {
                return 0;
            }
        }
    );

    this.searchableFields = searchableFields;
};

Fisma.Search.Panel.prototype = {
    
    /**
     * The parent container for this search panel
     */
    container : null,
    
    /**
     * A list of current selected criteria
     */
    criteria : [],
    
    /**
     * Render the advanced search box
     * 
     * @param container The HTML container to place the search box inside of
     */
    render : function (container) {
        this.container = container;
        
        var initialCriteria = new Fisma.Search.Criteria(this, this.searchableFields);

        var criteriaElement = initialCriteria.render();
        
        this.container.appendChild(criteriaElement);
    },
    
    
    /**
     * Add a criteria row below the specified row
     * 
     * @param currentRow The HTML container for the row that the new row will be placed under
     */
    addCriteria : function (currentRow) {
        var criteria = new Fisma.Search.Criteria(this, this.searchableFields);

        var criteriaElement = criteria.render();
        
        this.container.insertBefore(criteriaElement, currentRow.nextSibling);
    },
    
    /**
     * Remove the specified criteria row
     * 
     * @param currentRow The HTML container for the row that needs to be removed
     */
    removeCriteria : function (currentRow) {
        this.container.removeChild(currentRow);
    }
};
