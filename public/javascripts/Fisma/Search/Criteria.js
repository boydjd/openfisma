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
 * @fileoverview Implements a single criteria row in the advanced search interface
 * 
 * @author    Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright (c) Endeavor Systems, Inc. 2010 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/content/license
 */

/**
 * Constructor
 * 
 * @param searchPanel The search panel object that created this criteria object
 * @param fields List of fields and data types that are searchable
 */
Fisma.Search.Criteria = function (searchPanel, fields) {

    if (0 == fields.length) {
        throw "Field array cannot be empty";
    }

    this.currentFieldType = null;
    this.currentFieldName = null;
    this.currentQueryType = null;
    this.fields = fields;
    this.searchPanel = searchPanel;

    this.container = null;

    this.queryFieldContainer = null;
    this.queryTypeContainer = null;
    this.queryInputContainer = null;
    this.buttonsContainer = null;
};

Fisma.Search.Criteria.prototype = {
    
    /**
     * Render the criteria widget
     * 
     * @param defaultFieldIndex An integer index into the searchable fields array which indicates the default field
     * @return An HTML element containing the search criteria widget
     */
    render : function (defaultFieldIndex) {
        
        this.container = document.createElement('div');
        
        this.container.className = "searchCriteria";
        
        this.queryFieldContainer = this.renderQueryField(this.container, defaultFieldIndex);
        this.queryTypeContainer = this.renderQueryType(this.container);
        this.queryInputContainer = this.renderQueryInput(this.container);
        this.buttonsContainer = this.renderButtons(this.container);
        
        var clearDiv = document.createElement('div');
        
        clearDiv.className = "clear";
        
        this.container.appendChild(clearDiv);

        return this.container;
    },
    
    /**
     * Renders a YUI menu button that behaves like a select element. This element is where the user selects the field
     * to query on.
     * 
     * @param The HTML element that contains the select widget
     * @param defaultFieldIndex An integer index into the searchable fields array which indicates the default field
     */
    renderQueryField : function (container, defaultFieldIndex) {
        
        var that = this;

        var spanEl = document.createElement('span');
        
        var menuItems = new Array();
        var menuButton;

        // This event handler makes the menu button behave like a popup menu
        var menuClickHandler = function (type, args, item) {
            var newLabel = item.cfg.getProperty("text");

            that.currentFieldName = item.value;

            for (var index in this.fields) {
                var field = this.fields[index];
                
                that.currentFieldType = field.type;
                
                break;
            }

            menuButton.set("label", newLabel);
        };

        // Convert field list to menu items
        for (var index in this.fields) {
            var field = this.fields[index];

            menuItems.push({
                text : field.label,
                value : field.name,
                onclick : {fn : menuClickHandler}
            });
        }

        // Render menu button
        var initialFieldIndex = defaultFieldIndex % this.fields.length;
        
        this.currentFieldName = this.fields[initialFieldIndex].name;
        this.currentFieldType = this.fields[initialFieldIndex].type;

        menuButton = new YAHOO.widget.Button({
            type : "menu", 
            label : this.fields[initialFieldIndex].label, 
            menu : menuItems,
            container : spanEl
        });

        container.appendChild(spanEl);
        
        return spanEl;
    },
    
    /**
     * Render the query type menu based on the current item's type
     * 
     * @param container The HTML element that contains the query fields
     */
    renderQueryType : function (container) {

        var spanEl = document.createElement('span');

        switch (this.currentFieldType) {
            case "text":
                this.renderQueryTypeText(spanEl);
                break;
                
            default:
                throw "Undefined search field type: " + this.currentFieldType;
        }
        
        container.appendChild(spanEl);
        
        return spanEl;
    },

    /**
     * Render the actual query criteria fields based on the query's type
     * 
     * @param container The HTML element that contains the query fields
     */
    renderQueryInput : function (container) {
        var spanEl = document.createElement('span');

        switch (this.currentQueryType) {
            
            // These cases intentionally fall through
            case "beginsWith":
            case "contains":
            case "endsWith":
            case "is":
                this.renderQueryInputText(spanEl);
                break;
                
            default:
                throw "Undefined query type: " + this.currentQueryType;
        }
        
        container.appendChild(spanEl);
        
        return spanEl;
        
    },

    /**
     * Render the add/remove buttons that the user uses to create additional search criteria or remove existing 
     * criteria
     * 
     * @param The HTML element that contains the add/remove buttons
     */
    renderButtons : function (container) {

        var that = this;

        var spanEl = document.createElement('span');

        spanEl.className = "searchQueryButtons";
        
        var addButton = new YAHOO.widget.Button({container : spanEl});

        addButton._button.className = "searchAddCriteriaButton";
        addButton._button.title = "Click to add another search criteria";

        addButton.on(
            "click", 
            function () {
                that.searchPanel.addCriteria(that.container);
            }
        );

        var removeButton = new YAHOO.widget.Button({container : spanEl});

        removeButton._button.className = "searchRemoveCriteriaButton";
        removeButton._button.title = "Click to remove this search criteria";

        removeButton.on(
            "click", 
            function () {
                that.searchPanel.removeCriteria(that.container);
            }
        );

        container.appendChild(spanEl);

        return spanEl;
    },
    
    /**
     * Render text field query options
     * 
     * @param The HTML element that contains the add/remove buttons
     */
    renderQueryTypeText : function (container) {

        var menuItems = new Array();
        var menuButton;
        
        var that = this;

        // This event handler makes the menu button behave like a popup menu
        var menuClickHandler = function (type, args, item) {
            var newLabel = item.cfg.getProperty("text");

            that.currentQueryType = item.value;

            menuButton.set("label", newLabel);
        };

        // Create menu from the available query types
        menuItems = [
            {
                text : "Begins With",
                value : "beginsWith",
                onclick : {fn : menuClickHandler}                
            },
            {
                text : "Contains",
                value : "contains",
                onclick : {fn : menuClickHandler}
            },
            {
                text : "Ends With",
                value : "endsWith",
                onclick : {fn : menuClickHandler}                
            },
            {
                text : "Is",
                value : "is",
                onclick : {fn : menuClickHandler}
            }
        ];

        // The default selection is "Contains"
        var defaultMenuItemIndex = 1;

        this.currentQueryType = menuItems[defaultMenuItemIndex].value;

        // Render menu button
        menuButton = new YAHOO.widget.Button({
            type : "menu", 
            label : menuItems[defaultMenuItemIndex].text,
            menu : menuItems,
            container : container
        });
    },
    
    /**
     * Render the search criteria as a single text input
     */
    renderQueryInputText : function (container) {
        var textEl = document.createElement('input');
        
        textEl.type = "text";
        
        container.appendChild(textEl);
    },
    
    /**
     * Get the URL query string for this criteria in its current state
     * 
     * The URL query string has one URL parameter for each of the criteria fields for the current query type. The name 
     * of the parameter is formed by concatenating the field name with a dot and the query type. For example, the 
     * following query string is returned for a query on "Creation Date Between Jan 1 2010 and Jun 30 2010":
     * 
     *     /creationDate.greaterThan/2010-01-01/creationDate.lessThan/2010-06-30/
     */
    getQueryString : function () {
        
        var queryString = '';

        switch (this.currentQueryType) {
            // These cases all intentionally fall through
            case 'beginsWith':
            case 'contains':
            case 'endsWith':
            case 'is':
                var inputs = this.queryInputContainer.getElementsByTagName('input');

                queryString = '/' + this.currentFieldName + '.' + this.currentQueryType + '/' + inputs[0].value;
                break;
            
            default:
                throw "No query string is implemented for the following query type: " + this.currentQueryType;
        }

        return queryString;
    }
};