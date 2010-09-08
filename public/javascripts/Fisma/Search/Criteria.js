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
    this.fields = fields;
    this.searchPanel = searchPanel;
};

Fisma.Search.Criteria.prototype = {

    /**
     * The HTML element to render this widget into
     */
    container : null,

    /**
     * The name of the currently selected field
     */
    currentFieldName : null,

    /**
     * The type of the currently selected field
     */
    currentFieldType : null,

    /**
     * The type of the currently selected query
     */
    currentQueryType : null,

    /**
     * An array of field descriptions
     */
    fields : null,

    /**
     * A reference to the search panel that this criteria widget is a part of
     */
    searchPanel : null,

    /**
     * The HTML element that holds the query field selection UI.
     *
     * The query field is the field on the model which this criteria applies to.
     */
    queryFieldContainer : null,

    /**
     * The HTML element that the holds query type selection UI.
     *
     * The query type refers to the type of criteria applied to the current field, such as "Contains" or
     * "Greater Than".
     */
    queryTypeContainer : null,

    /**
     * The HTML element that holds the query input parameter UI.
     *
     * The query input is the user-supplied value to search for, such as a keyword or a range of date values
     */
    queryInputContainer : null,

    /**
     * The HTML element that holds the add/remove buttons UI.
     *
     * These buttons are used to add and remove criteria rows, respectively.
     */
    buttonsContainer : null,

    /**
     * A reference to the remove button
     */
    removeButton : null,

    /**
     * Render the criteria widget
     *
     * @param defaultFieldIndex An integer index into the searchable fields array which indicates the default field
     * @return An HTML element containing the search criteria widget
     */
    render : function (defaultFieldIndex) {

        this.container = document.createElement('div');

        this.container.className = "searchCriteria";

        this.queryFieldContainer = document.createElement('span');
        this.renderQueryField(this.queryFieldContainer, defaultFieldIndex);
        this.container.appendChild(this.queryFieldContainer);

        this.queryTypeContainer = document.createElement('span');
        this.renderQueryType(this.queryTypeContainer);
        this.container.appendChild(this.queryTypeContainer);

        this.queryInputContainer = document.createElement('span');
        this.renderQueryInput(this.queryInputContainer);
        this.container.appendChild(this.queryInputContainer);

        this.buttonsContainer = document.createElement('span');
        this.buttonsContainer.className = "searchQueryButtons";
        this.renderButtons(this.buttonsContainer);
        this.container.appendChild(this.buttonsContainer);

        var clearDiv = document.createElement('div');

        clearDiv.className = "clear";

        this.container.appendChild(clearDiv);

        return this.container;
    },

    /**
     * Renders a YUI menu button that behaves like a select element. This element is where the user selects the field
     * to query on.
     *
     * @param container The HTML element to render into
     * @param defaultFieldIndex An integer index into the searchable fields array which indicates the default field
     */
    renderQueryField : function (container, defaultFieldIndex) {

        var that = this;

        var menuItems = new Array();
        var menuButton;

        // This event handler makes the menu button behave like a popup menu
        var menuClickHandler = function (type, args, item) {

            var newLabel = item.cfg.getProperty("text");

            for (var index in that.fields) {
                var field = that.fields[index];

                if (item.value == field.name) {

                    that.currentFieldName = field.name;

                    if (that.currentFieldType != field.type) {
                        that.currentFieldType = field.type;

                        that.renderQueryType(that.queryTypeContainer);
                    }

                    break;
                }
            }

            menuButton.set("label", field.label);
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
            container : container
        });
    },

    /**
     * Render the query type menu based on the current item's type
     *
     * @param container The HTML element to render into
     */
    renderQueryType : function (container) {

        // Remove any existing content in this container
        if (container.firstChild) {
            while (container.hasChildNodes()) {
                container.removeChild(container.firstChild);
            }
        }

        var that = this;

        // This event handler makes the menu button behave like a popup menu
        var handleQueryTypeSelectionEvent = function (type, args, item) {
            var newLabel = item.cfg.getProperty("text");

            that.currentQueryType = item.value;
            that.renderQueryInput(that.queryInputContainer);

            menuButton.set("label", newLabel);
        };

        // Load the criteria definition
        var criteriaType = this.currentFieldType;

        if ('datetime' == criteriaType) {
            // 'datetime' is aliased to 'date' since they behave the same
            criteriaType = 'date';
        }

        var criteriaDefinitions = this.getCriteriaDefinition(criteriaType);

        // Create the select menu
        var menuItems = new Array();

        for (var criteriaType in criteriaDefinitions) {
            var criteriaDefinition = criteriaDefinitions[criteriaType];

            menuItem = {
                text : criteriaDefinition.label,
                value : criteriaType,
                onclick : {fn : handleQueryTypeSelectionEvent}
            };

            menuItems.push(menuItem);

            if (criteriaDefinition.isDefault) {
                this.currentQueryType = criteriaType;
            }
        }

        // Render menu button
        var menuButton = new YAHOO.widget.Button({
            type : "menu",
            label : criteriaDefinitions[this.currentQueryType].label,
            menu : menuItems,
            container : container
        });
    },

    /**
     * Render the actual query criteria fields based on the query's type
     *
     * @param container The HTML element that contains the query fields
     */
    renderQueryInput : function (container) {

        // Remove any existing content in this container
        if (container.firstChild) {
            while (container.hasChildNodes()) {
                container.removeChild(container.firstChild);
            }
        }

        // Call the defined renderer for the selected query type
        var criteriaDefinitions = this.getCriteriaDefinition(this.currentFieldType);
        var rendererName = criteriaDefinitions[this.currentQueryType].renderer;
        var render = Fisma.Search.CriteriaRenderer[rendererName];

        render(container);
    },

    /**
     * Render the add/remove buttons that the user uses to create additional search criteria or remove existing
     * criteria
     *
     * @param The HTML element to render into
     */
    renderButtons : function (container) {

        var that = this;

        var addButton = new YAHOO.widget.Button({container : container});

        addButton._button.className = "searchAddCriteriaButton";
        addButton._button.title = "Click to add another search criteria";

        addButton.on(
            "click",
            function () {
                that.searchPanel.addCriteria(that.container);
            }
        );

        var removeButton = new YAHOO.widget.Button({container : container});

        removeButton._button.className = "searchRemoveCriteriaButton";
        removeButton._button.title = "Click to remove this search criteria";

        removeButton.on(
            "click",
            function () {
                that.searchPanel.removeCriteria(that.container);
            }
        );

        this.removeButton = removeButton;
    },

    /**
     * Get the query data for this criteria in its current state
     *
     * The query is returned as an object including the field name, the operator, and 0-n operands
     */
    getQuery : function () {

        var queryString = '';
        var criteriaDefinitions = this.getCriteriaDefinition(this.currentFieldType);

        var queryGeneratorName = criteriaDefinitions[this.currentQueryType].query;
        var queryGenerator = Fisma.Search.CriteriaQuery[queryGeneratorName];

        var query = queryGenerator(this.queryInputContainer);

        var response = {
            field : this.currentFieldName,
            operator : this.currentQueryType,
            operands : query
        }

        return response;
    },

    /**
     * Returns the criteria definition for a given field type
     *
     * This covers up the fact that 'date' and 'datetime' behave the same way
     *
     * @param fieldType
     */
    getCriteriaDefinition : function (fieldType) {
        var tempType = fieldType;

        if ('datetime' == tempType) {
            tempType = 'date';
        }

        return Fisma.Search.CriteriaDefinition[tempType];
    },

    /**
     * Enable or disable the remove button
     *
     * The button is disabled when there is only 1 criterion left in the panel, so that the user cannot remove ALL
     * of the criteria. (This would put the UI into an unusable state since there is no way to add criteria at
     * that point.)
     *
     * @param bool enabled
     */
    setRemoveButtonEnabled : function (enabled) {
        this.removeButton.set("disabled", !enabled);
    }
};