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

    this.currentField = fields[0];
    this.currentQueryType = null;
    this.fields = fields;
    this.searchPanel = searchPanel;

    this.container = null;

    this.selectContainer = null;
    this.queryTypeContainer = null;
    this.queryInputContainer = null;
    this.buttonsContainer = null;
};

Fisma.Search.Criteria.prototype = {
    
    /**
     * Render the criteria widget
     * 
     * @param parentContainer The HTML element that contains this widget
     * @return An HTML element containing the search criteria widget
     */
    render : function () {
        
        this.container = document.createElement('div');
        
        this.container.className = "searchCriteria";
        
        this.selectContainer = this.renderSelect(this.container);
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
     */
    renderSelect : function (container) {
        
        var spanEl = document.createElement('span');
        
        var menuItems = new Array();
        var menuButton;

        // This event handler makes the menu button behave like a popup menu
        var menuClickHandler = function (type, args, item) {
            var newLabel = item.cfg.getProperty("text");

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
        menuButton = new YAHOO.widget.Button({
            type : "menu", 
            label : this.fields[0].label, 
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

        switch (this.currentField.type) {
            case "text":
                this.renderQueryTypeText(spanEl);
                break;
                
            default:
                throw "Undefined search field type: " + this.currentField.type;
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

        // This event handler makes the menu button behave like a popup menu
        var menuClickHandler = function (type, args, item) {
            var newLabel = item.cfg.getProperty("text");

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

        // Render menu button
        menuButton = new YAHOO.widget.Button({
            type : "menu", 
            label : menuItems[0].text,
            menu : menuItems,
            container : container
        });

        this.currentQueryType = menuItems[0].value;
    },
    
    /**
     * Render the search criteria as a single text input
     */
    renderQueryInputText : function (container) {
        var textEl = document.createElement('input');
        
        textEl.type = "text";
        
        container.appendChild(textEl);
    }
};