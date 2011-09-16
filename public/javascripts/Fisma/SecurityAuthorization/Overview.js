/**
 * Copyright (c) 2011 Endeavor Systems, Inc.
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
 * @author    Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @license   http://www.openfisma.org/content/license
 */

(function() {
    /**
     * Represents the progress of the steps in the RMF process
     * 
     * @namespace Fisma.SecurityAuthorization
     * @class Overview
     * @extends Object
     * @constructor
     * @param steps {Array} An array of step information
     */
    var Overview = function(steps) {
        this._steps = steps;

        Overview.superclass.constructor.call(this);
    };
    
    YAHOO.lang.extend(Overview, Object, {

        /**
         * An array of step information
         * 
         * @property _steps
         * @type Array
         * @protected
         */        
        _steps: null,

        /**
         * Render the overview widget
         * 
         * @param {HTMLElement} container
         */
        render: function (container) {
            var tableEl = document.createElement('table');
            tableEl.className = "saOverview";
            
            // Create table headers
            var tableHeader = document.createElement('tr');

            var stepTh = document.createElement('th');
            stepTh.appendChild(document.createTextNode("Step"));
            tableHeader.appendChild(stepTh);

            var statusTh = document.createElement('th');
            statusTh.appendChild(document.createTextNode("Status"));
            tableHeader.appendChild(statusTh);
            
            tableEl.appendChild(tableHeader);

            // Create 1 row for each step
            for (var i in this._steps) {
                var step = this._steps[i];
                
                var rowEl = document.createElement('tr');
                
                // Create the "step" cell
                var cellEl = document.createElement('td');
                var anchor = document.createElement('a');
                anchor.href = "#";
                anchor.appendChild(document.createTextNode(step.name));
                cellEl.appendChild(anchor);
                rowEl.appendChild(cellEl);

                anchor.onclick = (function (tabId) {
                    // Creating a closure inside a loop is awkward…
                    return function () {
                        Fisma.tabView.selectTab(tabId);
                    }
                })(step.stepNumber);

                // Create the "status" cell
                cellEl = this._renderStatusCellForStep(step);
                rowEl.appendChild(cellEl);
                tableEl.appendChild(rowEl);
                
                // Save a reference to the status element so that it's easy to find/update later
                step.statusTd = cellEl;
            }
            
            container.appendChild(tableEl);
        },
        
        /**
         * Update the status table with new progress information for a specific step
         * 
         * @param stepNumber {integer}
         * @param numerator {integer} Optional. If not specified, then current value is used.
         * @param denominator {integer} Optional. If not specified, then current value is used.
         */
        updateStepProgress: function (stepNumber, numerator, denominator) {
            var step = this._steps[stepNumber - 1];
            
            if (YAHOO.lang.isUndefined(step)) {
                throw "Could not find step #" + stepNumber;
            }

            if (YAHOO.lang.isValue(numerator)) {
                step.numerator = numerator;
            }

            if (YAHOO.lang.isValue(denominator)) {
                step.denominator = denominator;
            }

            // Create a new status td, then swap it out for the old status td
            var newTd = this._renderStatusCellForStep(step);
            var parent = step.statusTd.parentNode;
            parent.replaceChild(newTd, step.statusTd);
            step.statusTd = newTd;
        },
        
        /**
         * Increase the numerator on the progress for a particular step
         * 
         * @param stepNumber {integer}
         * @param addend {integer} Defaults to 1
         */
        incrementStepNumerator: function (stepNumber, addend) {
            var step = this._steps[stepNumber - 1];
            var addend = addend || 1;

            this.updateStepProgress(stepNumber, step.numerator + addend, null);
        },

        /**
         * Increase the denominator on the progress for a particular step
         * 
         * @param stepNumber {integer}
         * @param addend {integer} Defaults to 1
         */
        incrementStepDenominator: function (stepNumber, addend) {
            var step = this._steps[stepNumber - 1];
            var addend = addend || 1;

            this.updateStepProgress(stepNumber, null, step.denominator + addend);
        },
        
        /**
         * Render the status cell for a particular step
         * 
         * @param step {Array}
         */
        _renderStatusCellForStep: function (step) {
            var td = document.createElement('td');

            var pieChartEl = document.createElement('span');

            var pieNumerator = step.numerator;
            var pieDenominator = step.denominator;
            
            // If the denominator is zero, then we want to show an empty pie chart, so pretend the ratio is actually 0/1
            // instead of n/0.
            if (pieDenominator == 0) {
                pieNumerator = 0;
                pieDenominator = 1;
            }

            pieChartEl.appendChild(document.createTextNode(pieNumerator + '/' + pieDenominator));
            jQuery(pieChartEl).peity("pie");
            td.appendChild(pieChartEl);

            var statusText = ' ' + step.completed;
            
            if (step.stepNumber == 3 || step.stepNumber == 4) {
                statusText += ' (' 
                            + step.numerator 
                            + ' of ' 
                            + step.denominator
                            + ')';
            }

            td.appendChild(document.createTextNode(statusText));

            return td;
        }
    });

    Fisma.SecurityAuthorization.Overview = Overview;
})();
