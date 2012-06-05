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
 * @fileoverview Generates URL query strings for various kinds of search criteria
 *
 * @author    Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright (c) Endeavor Systems, Inc. 2010 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/content/license
 */

Fisma.Search.CriteriaQuery = (function () {
    return {
        /**
         * This is the simplest query generator, it doesn't generate anything!
         *
         * @param container The HTML element that contains the input fields
         */
        noInputs : function(container) {
            return [];
        },

        /**
         * Generates a query based on one input field
         *
         * @param container The HTML element that contains the input fields
         */
        oneInput : function (container) {
            var inputs = container.getElementsByTagName('input');

            var values = [inputs[0].value];

            return values;
        },

        /**
         * Generates a query based on one input field
         *
         * @param container The HTML element that contains the input fields
         */
        csvInput : function (container) {
            var inputs = container.getElementsByTagName('input');

            var values = inputs[0].value.split(',');

            return values;
        },

        /**
         * Generates a query based on two input fields
         *
         * @param container The HTML element that contains the input fieldss
         */
        twoInputs : function (container) {
            var inputs = container.getElementsByTagName('input');

            var values = [inputs[0].value, inputs[1].value];

            return values;
        },

        /**
         * Generate a query based on a YUI menu button
         *
         * @param container the HTML element that contains the input fields
         */
        enumSelect : function (container) {
            var inputs = container.getElementsByTagName('button');

            var values = [inputs[0].firstChild.nodeValue];

            return values;
        }
    };
}());