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
     * The type of the currently selected query
     */
    currentQueryType : null,

    /**
     * An array of field descriptions
     */
    fields : null,

    /**
     * Metadata about the currently selected field
     */
    currentField : null,

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
     * Holds current enum values if the currently selected criterion is an enum field (null otherwise)
     */
    enumValues : null,

    /**
     * Render the criteria widget
     *
     * @param fieldName The name of the field to select (Required)
     * @param operator The name of the operator to select (Optional)
     * @param operands Values of operands to fill in (Optional)
     * @return An HTML element containing the search criteria widget
     */
    render : function (fieldName, operator, operands) {

        this.container = document.createElement('div');

        this.container.className = "searchCriteria";

        // IE7 will display floated elements on the next line, not the current line, unless those floated elements
        // are inserted before the unfloated content on current line.
        this.buttonsContainer = document.createElement('span');
        this.buttonsContainer.className = "searchQueryButtons";
        this.renderButtons(this.buttonsContainer);
        this.container.appendChild(this.buttonsContainer);

        this.queryFieldContainer = document.createElement('span');
        this.renderQueryField(this.queryFieldContainer, fieldName);
        this.container.appendChild(this.queryFieldContainer);

        this.queryTypeContainer = document.createElement('span');
        this.renderQueryType(this.queryTypeContainer, operator);
        this.container.appendChild(this.queryTypeContainer);

        this.queryInputContainer = document.createElement('span');
        this.renderQueryInput(this.queryInputContainer, operands);
        this.container.appendChild(this.queryInputContainer);

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
     * @param fieldName The name of the default field
     */
    renderQueryField : function (container, fieldName) {

        var that = this;

        var menuItems = [];
        var menuButton;

        // This event handler makes the menu button behave like a popup menu
        var handleQueryFieldSelectionEvent = function (type, args, item) {

            var newLabel = item.cfg.getProperty("text");
            var index, field;

            for (index in that.fields) {
                field = that.fields[index];

                if (item.value === field.name) {

                    // If a widget is already displayed that still applies to this new field, then leave it alone
                    // (Re-rendering it will set it back to its initial state, which is an annoying behavior.)
                    var refreshQueryType = true;
                    var refreshQueryInput = true;

                    if (that.getCriteriaDefinition(field) === that.getCriteriaDefinition(that.currentField)) {
                        refreshQueryType = false;
                    }

                    if ('enum' === field.type) {
                        refreshQueryInput = true;
                    }

                    that.currentField = field;

                    that.enumValues = field.enumValues;

                    if (refreshQueryType) {
                        that.renderQueryType(that.queryTypeContainer);
                    }

                    if (refreshQueryInput) {
                        that.renderQueryInput(that.queryInputContainer);
                    }

                    break;
                }
            }

            menuButton.set("label", field.label);
        };

        // Convert field list to menu items
        var index;
        for (index in this.fields) {
            var field = this.fields[index];

            menuItems.push({
                text : field.label,
                value : field.name,
                onclick : {fn : handleQueryFieldSelectionEvent}
            });
        }

        // Render menu button
        this.currentField = this.getField(fieldName);

        menuButton = new YAHOO.widget.Button({
            type : "menu",
            label : this.currentField.label,
            menu : menuItems,
            container : container
        });
    },

    /**
     * Render the query type menu based on the current item's type
     *
     * @param container The HTML element to render into
     * @param operator The default operator (optional)
     */
    renderQueryType : function (container, operator) {

        // Remove any existing content in this container
        if (container.firstChild) {
            while (container.hasChildNodes()) {
                container.removeChild(container.firstChild);
            }
        }

        var that = this;
        var menuButton;

        // This event handler makes the menu button behave like a popup menu
        var handleQueryTypeSelectionEvent = function (type, args, item) {
            var newLabel = item.cfg.getProperty("text");

            var criteria = that.getCriteriaDefinition(that.currentField);
            var oldRenderer = criteria[that.currentQueryType].renderer;
            var newRenderer = criteria[item.value].renderer;

            that.currentQueryType = item.value;

            if (oldRenderer !== newRenderer || 'enum' === that.currentField.type) {
                that.renderQueryInput(that.queryInputContainer);
            }

            menuButton.set("label", newLabel);
        };

        // Load the criteria definition
        var criteriaDefinitions = this.getCriteriaDefinition(this.currentField);

        // Create the select menu
        var menuItems = [];
        var criteriaType;

        for (criteriaType in criteriaDefinitions) {
            var criteriaDefinition = criteriaDefinitions[criteriaType];

            var menuItem = {
                text : criteriaDefinition.label,
                value : criteriaType,
                onclick : {fn : handleQueryTypeSelectionEvent}
            };

            menuItems.push(menuItem);

            if (criteriaDefinition.isDefault) {
                this.currentQueryType = criteriaType;
            }
        }

        // If the operator is specified, it overrules the 'default' designation in the criteria definitions
        if (operator) {
            this.currentQueryType = operator;
        }

        // Render menu button
        menuButton = new YAHOO.widget.Button({
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
     * @param operands An array of operands to set the inputs' values to
     */
    renderQueryInput : function (container, operands) {

        // Remove any existing content in this container
        if (container.firstChild) {
            while (container.hasChildNodes()) {
                container.removeChild(container.firstChild);
            }
        }

        // Call the defined renderer for the selected query type
        var criteriaDefinitions = this.getCriteriaDefinition(this.currentField);

        var rendererName = criteriaDefinitions[this.currentQueryType].renderer;
        var render = Fisma.Search.CriteriaRenderer[rendererName];

        if ('enum' === this.currentField.type) {
            render(container, operands, this.currentField.enumValues);
        } else {
            render(container, operands);
        }
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
        return {
            field : this.currentField.name,
            operator : this.currentQueryType,
            operands : this.getOperands()
        };
    },

    /**
     * Returns the criteria definition for a given field
     *
     * @param field
     */
    getCriteriaDefinition : function (field) {

        // Some mapping between the field's declared type and its inferred type
        var tempType = field.type;

        if ('datetime' === tempType) {
            tempType = 'date';
        } else if ('text' === tempType) {
            if (field.sortable) {
                tempType = 'sortableText';
            } else {
                tempType = 'nonSortableText';
            }
        }

        var definition = Fisma.Search.CriteriaDefinition[tempType];

        // Fields can define extra criteria that should be merged in
        if (field.extraCriteria) {
            var index;
            for (index in field.extraCriteria) {
                definition[index] = field.extraCriteria[index];
            }
        }

        return definition;
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
    },

    /**
     * Fields is numerically indexed. This is a helper function to find a field by name.
     */
    getField : function (fieldName) {
        var index;
        for (index in this.fields) {
            var field = this.fields[index];

            if (field.name === fieldName) {
                return field;
            }
        }

        throw "No field found with this name: " + fieldName;
    },

    getOperands: function() {
        var criteriaDefinitions = this.getCriteriaDefinition(this.currentField);
        var queryGeneratorName = criteriaDefinitions[this.currentQueryType].query;
        var queryGenerator = Fisma.Search.CriteriaQuery[queryGeneratorName];

        return queryGenerator(this.queryInputContainer);
    },

    hasBlankOperands: function() {
        var operands = this.getOperands();
        var i;
        for (i in operands) {
            if ('' === $P.trim(operands[i])) {
                return true;
            }
        }

        return false;
    }
};
