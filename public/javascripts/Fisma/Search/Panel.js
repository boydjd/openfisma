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
 * 
 * @fileoverview Implements a UI to display collection of advanced search criteria
 * 
 * @author    Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright (c) Endeavor Systems, Inc. 2010 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/content/license
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
     * Render the advanced search box including one criteria widget
     * 
     * @param container The HTML container to place the search box inside of
     */
    render : function (container) {
        this.container = container;

        // Update internal state
        var initialCriteria = new Fisma.Search.Criteria(this, this.searchableFields);
        this.criteria.push(initialCriteria);

        // Update DOM
        var defaultFieldIndex = this.criteria.length - 1;

        var criteriaElement = initialCriteria.render(defaultFieldIndex);
        this.container.appendChild(criteriaElement);
    },
  
    /**
     * Add a criteria row below the specified row
     * 
     * @param currentRow The HTML container for the row that the new row will be placed under
     */
    addCriteria : function (currentRow) {
        // Update internal state
        var criteria = new Fisma.Search.Criteria(this, this.searchableFields);
        this.criteria.push(criteria);
        
        // Update DOM
        var defaultFieldIndex = this.criteria.length - 1;

        var criteriaElement = criteria.render(defaultFieldIndex);
        this.container.insertBefore(criteriaElement, currentRow.nextSibling);
    },
    
    /**
     * Remove the specified criteria row
     * 
     * @param currentRow The HTML container for the row that needs to be removed
     */
    removeCriteria : function (currentRow) {
        // Update internal state
        for (var index in this.criteria) {
            var criterion = this.criteria[index];
            
            if (criterion.container == currentRow) {
                this.criteria.splice(index, 1);
                
                break;
            }
        }

        // Update DOM
        this.container.removeChild(currentRow);
    },
    
    /**
     * Get the URL query string for the current filter status
     */
    getUrlQuery : function () {
        var queryString = '';
        
        for (var index in this.criteria) {
            var criterion = this.criteria[index];

            queryString += criterion.getQueryString();
        }
        
        return queryString;
    }
};
