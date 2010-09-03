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
 
Fisma.Search.CriteriaRenderer = function () {
    return {
        /**
         * Renders two date fields with the word "And" between them
         * 
         * @todo Add date picker
         * 
         * @param container The HTML element to render into
         */
        betweenDate : function (container) {
            var lowEnd = document.createElement('input');        
            lowEnd.type = "text";
            lowEnd.className = "date";
            container.appendChild(lowEnd);
            Fisma.Calendar.addCalendarPopupToTextField(lowEnd);

            var text = document.createTextNode(" and ");
            container.appendChild(text);

            var highEnd = document.createElement('input');        
            highEnd.type = "text";
            highEnd.className = "date";
            container.appendChild(highEnd);
            Fisma.Calendar.addCalendarPopupToTextField(highEnd);
        },

        /**
         * Renders two integer fields with the word "And" between them
         * 
         * @param container The HTML element to render into
         */
        betweenInteger : function (container) {
            var lowEnd = document.createElement('input');        
            lowEnd.type = "text";
            lowEnd.className = "integer";
            container.appendChild(lowEnd);

            var text = document.createTextNode(" and ");
            container.appendChild(text);

            var highEnd = document.createElement('input');        
            highEnd.type = "text";
            highEnd.className = "integer";
            container.appendChild(highEnd);
        },

        /**
         * The simplest renderer. It doesn't do anything!
         * 
         * This is useful for search criteria that don't take any parameters
         * 
         * @param container The HTML element to render into
         */
        none : function (container) {
            console.log('test');
        },

        /**
         * Renders a single date input field
         * 
         * @todo Add date picker
         * 
         * @param container The HTML element to render into
         */
        singleDate : function (container) {
            
            // Create the input field
            var textEl = document.createElement('input');

            textEl.type = "text";
            textEl.className = "date";

            container.appendChild(textEl);
            
            Fisma.Calendar.addCalendarPopupToTextField(textEl);
        },
        
        /**
         * Renders a single integer input field
         * 
         * @param container The HTML element to render into
         */
        singleInteger : function (container) {
            var textEl = document.createElement('input');

            textEl.type = "text";
            textEl.className = "integer";

            container.appendChild(textEl);            
        },
        
        /**
         * Renders a plain old text field
         * 
         * @param container The HTML element to render into
         */
        text : function (container) {
            var textEl = document.createElement('input');

            textEl.type = "text";

            container.appendChild(textEl);
        }
    };
}();