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
 * @fileoverview AutoComplete namespace 
 *
 * @author    Josh Boyd <joshua.boyd@endeavorsystems.com>
 * @copyright (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license   http://www.openfisma.org/content/license
 * @package   Fisma
 * @requires  YAHOO.widget.AutoComplete
 * @requires  YAHOO.widget.DS_XHR
 * @requires  Fisma
 */

Fisma.AutoComplete = function() {
    return {
        /**
         * Used for tracking if there are any open requests.
         */
        requestCount : 0,
        
        /**
         * Used for tracking if any results have been populated
         */
        resultsPopulated : false,
        
        /**
         * Initializes the AutoComplete widget
         *
         * @param oEvent
         * @param aArgs
         * @param {Array} params 
         */
        init : function(oEvent, aArgs, params) {
            var acRDS = new YAHOO.widget.DS_XHR(params.xhr, params.schema);

            acRDS.responseType = YAHOO.widget.DS_XHR.TYPE_JSON;
            acRDS.maxCacheEntries = 500;
            acRDS.queryMatchContains = true;

            var ac = new YAHOO.widget.AutoComplete(params.fieldId, params.containerId, acRDS);

            ac.maxResultsDisplayed = 20;
            ac.forceSelection = true;

            var spinnerImage = document.getElementById(params.containerId + "Spinner");

            /**
             * Enable the spinner
             */
            ac.dataRequestEvent.subscribe(function () {
                spinnerImage.style.visibility = "visible";
                Fisma.AutoComplete.requestCount++;
            });

            /**
             * Disable the spinner if there are no pending requests
             */
            ac.dataReturnEvent.subscribe(function () {
                Fisma.AutoComplete.requestCount--;
                
                if (0 === Fisma.AutoComplete.requestCount) {
                    spinnerImage.style.visibility = "hidden";
                }
            });
            
            /**
             * Re-display the autocomplete menu if the text field loses and then regains focus
             */
            ac.getInputEl().onclick = function () {
                if (Fisma.AutoComplete.resultsPopulated) {
                    ac.expandContainer();
                }
            };
            
            /**
             * Record the fact that the results have been retrieved
             */
            ac.containerPopulateEvent.subscribe(function () {
                Fisma.AutoComplete.resultsPopulated = true;
            });
            
            /**
             * Override generateRequest method of YAHOO.widget.AutoComplete
             *
             * @param {String} query Query terms
             * @returns {String}
             */
            ac.generateRequest = function(query) {
                return params.queryPrepend + query;
            };
            
            /**
             * Overridable method that returns HTML markup for one result to be populated
             * as innerHTML of an <li> element.
             *
             * @method formatResult
             * @param oResultData {Object} Result data object.
             * @param sQuery {String} The corresponding query string.
             * @param sResultMatch {HTMLElement} The current query string.
             * @return {String} HTML markup of formatted result data.
             */
            ac.formatResult = function(oResultData, sQuery, sResultMatch) {
                var sMarkup = (sResultMatch) ? sResultMatch : "";
                sMarkup = PHP_JS().htmlspecialchars(sMarkup);
                return sMarkup;
            };

            ac.itemSelectEvent.subscribe(
                Fisma.AutoComplete.subscribe, 
                {
                    hiddenFieldId : params.hiddenFieldId,
                    callback : params.callback
                }
            );
        },

        /**
         * Sets value of hiddenField to item selected
         *
         * @param sType
         * @param aArgs
         * @param {Array} params
         */
        subscribe : function(sType, aArgs, params) {
            document.getElementById(params.hiddenFieldId).value = aArgs[2][1]['id'];
            $('#' + params.hiddenFieldId).trigger('change');
            // If a valid callback is specified, then call it
            try {
                var callbackFunction = Fisma.Util.getObjectFromName(params.callback);

                if ('function' == typeof callbackFunction) {
                    callbackFunction();
                }
            } catch (error) {
                // do nothing
            }
        }
    };
}();
