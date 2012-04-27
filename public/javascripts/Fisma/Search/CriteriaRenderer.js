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
 * @fileoverview Renders different types of search criteria
 *
 * @author    Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright (c) Endeavor Systems, Inc. 2010 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/content/license
 */

Fisma.Search.CriteriaRenderer = (function () {
    return {
        /**
         * Renders two date fields with the word "And" between them
         *
         * @todo Add date picker
         *
         * @param container The HTML element to render into
         * @param operands An array of default values
         */
        betweenDate : function (container, operands) {
            var lowEnd = document.createElement('input');

            if (operands && operands.length > 0) {
                lowEnd.value = operands[0];
            }

            lowEnd.type = "text";
            lowEnd.className = "date";
            container.appendChild(lowEnd);
            Fisma.Calendar.addCalendarPopupToTextField(lowEnd);
            Fisma.Search.onKeyPress(lowEnd);

            var text = document.createTextNode(" and ");
            container.appendChild(text);

            var highEnd = document.createElement('input');

            if (operands && operands.length > 1) {
                highEnd.value = operands[1];
            }

            highEnd.type = "text";
            highEnd.className = "date";
            container.appendChild(highEnd);
            Fisma.Calendar.addCalendarPopupToTextField(highEnd);
            Fisma.Search.onKeyPress(highEnd);
        },

        /**
         * Renders two float fields with the word "And" between them
         *
         * @param container The HTML element to render into
         * @param operands An array of default values
         */
        betweenFloat : function (container, operands) {
            var lowEnd = document.createElement('input');

            if (operands && operands.length > 0) {
                lowEnd.value = operands[0];
            }

            lowEnd.type = "text";
            lowEnd.className = "float";
            container.appendChild(lowEnd);
            Fisma.Search.onKeyPress(lowEnd);

            var text = document.createTextNode(" and ");
            container.appendChild(text);

            var highEnd = document.createElement('input');

            if (operands && operands.length > 1) {
                highEnd.value = operands[1];
            }

            highEnd.type = "text";
            highEnd.className = "float";
            container.appendChild(highEnd);
            Fisma.Search.onKeyPress(highEnd);
        },

        /**
         * Renders two integer fields with the word "And" between them
         *
         * @param container The HTML element to render into
         * @param operands An array of default values
         */
        betweenInteger : function (container, operands) {
            var lowEnd = document.createElement('input');

            if (operands && operands.length > 0) {
                lowEnd.value = operands[0];
            }

            lowEnd.type = "text";
            lowEnd.className = "integer";
            container.appendChild(lowEnd);
            Fisma.Search.onKeyPress(lowEnd);

            var text = document.createTextNode(" and ");
            container.appendChild(text);

            var highEnd = document.createElement('input');

            if (operands && operands.length > 1) {
                highEnd.value = operands[1];
            }

            highEnd.type = "text";
            highEnd.className = "integer";
            container.appendChild(highEnd);
            Fisma.Search.onKeyPress(highEnd);
        },

        /**
         * The simplest renderer. It doesn't do anything!
         *
         * This is useful for search criteria that don't take any parameters
         *
         * @param container The HTML element to render into
         * @param operands An array of default values
         */
        none : function (container, operands) {

        },

        /**
         * Renders a single date input field
         *
         * @todo Add date picker
         *
         * @param container The HTML element to render into
         * @param operands An array of default values
         */
        singleDate : function (container, operands) {

            // Create the input field
            var textEl = document.createElement('input');

            textEl.type = "text";
            textEl.className = "date";

            if (operands && operands.length > 0) {
                textEl.value = operands[0];
            }

            container.appendChild(textEl);
            Fisma.Search.onKeyPress(textEl);

            Fisma.Calendar.addCalendarPopupToTextField(textEl);
        },

        /**
         * Renders a single float input field
         *
         * @param container The HTML element to render into
         * @param operands An array of default values
         */
        singleFloat : function (container, operands) {
            var textEl = document.createElement('input');

            textEl.type = "text";
            textEl.className = "float";

            if (operands && operands.length > 0) {
                textEl.value = operands[0];
            }

            container.appendChild(textEl);
            Fisma.Search.onKeyPress(textEl);
        },

        /**
         * Renders a single integer input field
         *
         * @param container The HTML element to render into
         * @param operands An array of default values
         */
        singleInteger : function (container, operands) {
            var textEl = document.createElement('input');

            textEl.type = "text";
            textEl.className = "integer";

            if (operands && operands.length > 0) {
                textEl.value = operands[0];
            }

            container.appendChild(textEl);
            Fisma.Search.onKeyPress(textEl);
        },

        /**
         * Renders a plain old text field
         *
         * @param container The HTML element to render into
         * @param operands An array of default values
         */
        text : function (container, operands) {
            var textEl = document.createElement('input');

            textEl.type = "text";

            if (operands && operands.length > 0) {
                textEl.value = operands[0];
            }

            container.appendChild(textEl);
            Fisma.Search.onKeyPress(textEl);
        },

        /**
         * Render an enumeration field as a select menu
         *
         * @param container The HTML element to render into
         * @param operands An array of default values
         * @param enumValues An array of enumeration values
         */
        enumSelect : function (container, operands, enumValues) {
            var menuButton;

            // This event handler makes the menu button behave like a popup menu
            var handleEnumSelectionEvent = function (type, args, item) {
                var newLabel = item.cfg.getProperty("text");

                menuButton.set("label", newLabel);
            };

            // Create the select menu
            var menuItems = [];
            var index;

            for (index in enumValues) {
                var enumValue = enumValues[index];

                var menuItem = {
                    text : $P.htmlentities(enumValue, "ENT_NOQUOTES", "UTF-8"),
                    value : enumValue,
                    onclick : {fn : handleEnumSelectionEvent}
                };

                menuItems.push(menuItem);
            }

            // If an operand is supplied, that is the default value. Otherwise the default is the first enum value.
            var defaultValue = (operands && operands.length > 0) ? operands[0] : enumValues[0];
            defaultValue =  jQuery('<div/>').text(defaultValue).html();

            // Render menu button
            menuButton = new YAHOO.widget.Button({
                type : "menu",
                label : defaultValue,
                menu : menuItems,
                container : container
            });
        }
    };
}());
